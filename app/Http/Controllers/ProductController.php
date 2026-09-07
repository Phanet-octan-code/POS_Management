<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\ActivityLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only(['search', 'category_id', 'brand_id', 'status', 'stock_status']);

        $products = Product::with(['category', 'brand', 'supplier'])
            ->filter($filters)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        // Metrics for dashboard counters
        $totalProducts = Product::count();
        $lowStockCount = Product::whereColumn('stock_quantity', '<=', 'alert_quantity')->where('stock_quantity', '>', 0)->count();
        $outOfStockCount = Product::where('stock_quantity', '<=', 0)->count();

        if ($request->ajax() && $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'products' => $products,
                'metrics' => [
                    'total' => $totalProducts,
                    'low_stock' => $lowStockCount,
                    'out_of_stock' => $outOfStockCount,
                ],
            ]);
        }

        return view('products.index', compact('products', 'categories', 'brands', 'totalProducts', 'lowStockCount', 'outOfStockCount'));
    }

    public function create(): View
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        // Suggested defaults
        $suggestedSku = $this->generateUniqueSku();
        $suggestedBarcode = $this->generateUniqueBarcode();

        return view('products.create', compact('categories', 'brands', 'suppliers', 'suggestedSku', 'suggestedBarcode'));
    }

    public function store(ProductRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(5);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['wholesale_price'] = $data['wholesale_price'] ?? 0;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        DB::beginTransaction();
        try {
            $product = Product::create($data);

            // Synchronize initial stock level in product_stocks table
            ProductStock::create([
                'product_id' => $product->id,
                'location' => 'Main Store',
                'quantity' => $product->stock_quantity,
            ]);

            // If initial stock > 0, record opening stock movement
            if ($product->stock_quantity > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => 'in',
                    'quantity' => $product->stock_quantity,
                    'stock_before' => 0,
                    'stock_after' => $product->stock_quantity,
                    'reason' => 'Opening Stock',
                ]);
            }

            DB::commit();
            ActivityLoggerService::log('product.create', "created product '{$product->name}'", $product, ['sku' => $product->sku], 'products');

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Product '{$product->name}' created successfully.",
                    'product' => $product,
                ]);
            }

            return redirect()->route('products.index')->with('success', "Product '{$product->name}' created successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->with('error', "Error saving product: " . $e->getMessage());
        }
    }

    public function show(Product $product, Request $request): View|JsonResponse
    {
        $product->load(['category', 'brand', 'supplier', 'stocks', 'stockMovements' => function ($q) {
            $q->latest()->take(10)->with('user');
        }]);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'product' => $product,
                'image_url' => $product->image_url,
                'current_stock' => $product->current_stock,
                'is_low_stock' => $product->isLowStock(),
                'margin' => number_format($product->selling_price - $product->cost_price, 2),
                'margin_percent' => $product->cost_price > 0 ? round((($product->selling_price - $product->cost_price) / $product->cost_price) * 100, 1) : 0,
            ]);
        }

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('products.edit', compact('product', 'categories', 'brands', 'suppliers'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['wholesale_price'] = $data['wholesale_price'] ?? 0;

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        DB::beginTransaction();
        try {
            $oldStock = $product->stock_quantity;
            $product->update($data);

            // If stock quantity was updated directly from product edit, synchronize with product_stocks and record movement
            if ($oldStock != $product->stock_quantity) {
                $mainStock = ProductStock::firstOrCreate(
                    ['product_id' => $product->id, 'location' => 'Main Store'],
                    ['quantity' => $oldStock]
                );
                $diff = $product->stock_quantity - $oldStock;
                $mainStock->quantity = $product->stock_quantity;
                $mainStock->save();

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'type' => $diff >= 0 ? 'adjustment' : 'adjustment',
                    'quantity' => $diff,
                    'stock_before' => $oldStock,
                    'stock_after' => $product->stock_quantity,
                    'reason' => 'Direct inventory edit adjustment',
                ]);
            }

            DB::commit();
            ActivityLoggerService::log('product.update', "updated product '{$product->name}'", $product, [], 'products');

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Product '{$product->name}' updated successfully.",
                    'product' => $product,
                ]);
            }

            return redirect()->route('products.index')->with('success', "Product '{$product->name}' updated successfully.");
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->with('error', "Error updating product: " . $e->getMessage());
        }
    }

    public function toggleStatus(Product $product): JsonResponse|RedirectResponse
    {
        $product->is_active = !$product->is_active;
        $product->save();

        $statusStr = $product->is_active ? 'activated' : 'deactivated';
        ActivityLoggerService::log('product.status_updated', "{$statusStr} product '{$product->name}'", $product, [], 'products');

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $product->is_active,
                'message' => "Product '{$product->name}' {$statusStr}.",
            ]);
        }

        return back()->with('success', "Product '{$product->name}' {$statusStr}.");
    }

    public function destroy(Product $product): RedirectResponse|JsonResponse
    {
        $name = $product->name;

        // Check if product is tied to active sales or purchases
        $hasSales = $product->saleItems()->count();
        $hasPurchases = $product->purchaseItems()->count();

        if ($hasSales > 0 || $hasPurchases > 0) {
            // Soft delete to protect transaction history
            $product->delete();
            $msg = "Product '{$name}' was archived (soft-deleted) because it has transaction history.";
        } else {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $product->stocks()->delete();
            $product->delete();
            $msg = "Product '{$name}' was deleted successfully.";
        }

        ActivityLoggerService::log('product.delete', "deleted product '{$name}'", null, [], 'products');

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return redirect()->route('products.index')->with('success', $msg);
    }

    public function generateSku(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'sku' => $this->generateUniqueSku(),
        ]);
    }

    public function generateBarcode(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'barcode' => $this->generateUniqueBarcode(),
        ]);
    }

    private function generateUniqueSku(string $prefix = 'PRD'): string
    {
        do {
            $sku = $prefix . '-' . strtoupper(Str::random(6));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    private function generateUniqueBarcode(): string
    {
        do {
            // Generate a 12-digit standard UPC/EAN compliant string starting with 200 (internal store code)
            $barcode = '200' . str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);
        } while (Product::where('barcode', $barcode)->exists());

        return $barcode;
    }
}
