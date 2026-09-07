<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaleRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PosController extends Controller
{
    public function __construct(
        protected PosService $posService
    ) {}

    /**
     * Display the main POS terminal interface.
     */
    public function index(): View
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        // Load catalog products with category, brand, and calculated fields
        $products = Product::where('is_active', true)
            ->with(['category', 'brand'])
            ->orderBy('name')
            ->get();

        // Dynamic POS Terminal Settings
        $posSettings = [
            'default_tax' => (float) \App\Models\Setting::get('pos_default_tax', \App\Models\Setting::get('store_tax', 0)),
            'default_discount' => (float) \App\Models\Setting::get('pos_default_discount', 0),
            'receipt_size' => \App\Models\Setting::get('receipt_size', '80mm'),
            'enable_barcode' => (bool) ((int) \App\Models\Setting::get('enable_barcode', 1)),
            'enable_customer' => (bool) ((int) \App\Models\Setting::get('enable_customer', 1)),
            'enable_sound' => (bool) ((int) \App\Models\Setting::get('enable_sound', 1)),
            'currency_symbol' => \App\Models\Setting::get('currency_symbol', '$'),
            'store_name' => \App\Models\Setting::get('store_name', 'OmniPOS Superstore'),
        ];

        return view('pos.index', compact('categories', 'brands', 'customers', 'products', 'posSettings'));
    }

    /**
     * Search products dynamically for the POS catalog grid.
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->get('query', ''));
        $categoryId = $request->get('category_id');
        $brandId = $request->get('brand_id');

        $products = Product::where('is_active', true)
            ->with(['category', 'brand'])
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->when($brandId, fn($q) => $q->where('brand_id', $brandId))
            ->when($query, function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhere('barcode', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'products' => $products,
        ]);
    }

    /**
     * Fast-path barcode/SKU lookup to automatically add to cart on scan.
     */
    public function barcodeLookup(Request $request): JsonResponse
    {
        $barcode = trim($request->get('barcode', ''));

        if (!$barcode) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter or scan a barcode.',
            ], 400);
        }

        $product = Product::where('is_active', true)
            ->with(['category', 'brand'])
            ->where(function ($q) use ($barcode) {
                $q->where('barcode', $barcode)
                  ->orWhere('sku', $barcode);
            })
            ->first();

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => "No product found matching barcode/SKU: {$barcode}",
            ], 404);
        }

        if ($product->stock_quantity <= 0) {
            return response()->json([
                'success' => false,
                'message' => "Product '{$product->name}' is Out of Stock!",
                'product' => $product,
            ], 400);
        }

        return response()->json([
            'success' => true,
            'product' => $product,
        ]);
    }

    /**
     * Complete a POS sale transaction and deduct inventory.
     */
    public function checkout(SaleRequest $request): JsonResponse
    {
        try {
            $sale = $this->posService->processSale($request->validated());
            $sale->load(['items.product', 'customer', 'user']);

            return response()->json([
                'success' => true,
                'message' => "Order {$sale->invoice_no} completed successfully.",
                'sale_id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'sale_date' => $sale->sale_date->format('Y-m-d H:i'),
                'customer_name' => $sale->customer?->name ?? 'Walk-in Customer',
                'cashier_name' => $sale->user?->name ?? 'Staff',
                'subtotal' => number_format($sale->subtotal, 2),
                'discount_amount' => number_format($sale->discount_amount, 2),
                'tax_amount' => number_format($sale->tax_amount, 2),
                'total_amount' => number_format($sale->total_amount, 2),
                'paid_amount' => number_format($sale->paid_amount, 2),
                'change_amount' => number_format($sale->change_amount, 2),
                'due_amount' => number_format($sale->due_amount, 2),
                'payment_method' => $sale->payment_method_label,
                'payment_status' => $sale->payment_status_label,
                'items' => $sale->items->map(fn($item) => [
                    'name' => $item->product?->name ?? 'Product',
                    'quantity' => $item->quantity,
                    'unit_price' => number_format($item->unit_price, 2),
                    'subtotal' => number_format($item->subtotal, 2),
                ]),
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the transaction: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Printable thermal / standard POS invoice receipt.
     */
    public function invoice(Sale $sale): View
    {
        $sale->load(['items.product', 'customer', 'user']);
        return view('pos.invoice', compact('sale'));
    }
}
