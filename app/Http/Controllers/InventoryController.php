<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\ActivityLoggerService;
use App\Services\InventoryService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Display inventory list with stock levels, status filters, and valuation metrics.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only(['search', 'category_id', 'stock_status']);

        $query = Product::with(['category', 'brand'])->filter($filters);

        // Sorting: default to lowest stock first or latest
        $sortBy = $request->get('sort', 'stock_quantity');
        $sortDir = $request->get('direction', 'asc');
        $products = $query->orderBy($sortBy, $sortDir)->paginate(15)->withQueryString();

        // Key Performance Indicators
        $totalStockUnits = (int) Product::sum('stock_quantity');
        $totalCostValuation = (float) (Product::selectRaw('SUM(stock_quantity * cost_price) as val')->value('val') ?? 0);
        $totalRetailValuation = (float) (Product::selectRaw('SUM(stock_quantity * selling_price) as val')->value('val') ?? 0);

        // Stock status detection counts
        $outOfStockCount = Product::where('stock_quantity', '<=', 0)->count();
        $lowStockCount = Product::where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'alert_quantity')
            ->count();
        $inStockCount = Product::where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '>', 'alert_quantity')
            ->count();
        $totalProducts = Product::count();

        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $allProducts = Product::select('id', 'name', 'sku', 'stock_quantity', 'alert_quantity', 'unit')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        if ($request->ajax() && $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'products' => $products,
                'metrics' => [
                    'total_stock_units' => $totalStockUnits,
                    'total_cost_valuation' => $totalCostValuation,
                    'total_retail_valuation' => $totalRetailValuation,
                    'low_stock_count' => $lowStockCount,
                    'out_of_stock_count' => $outOfStockCount,
                    'in_stock_count' => $inStockCount,
                ],
            ]);
        }

        return view('inventory.index', compact(
            'products',
            'totalStockUnits',
            'totalCostValuation',
            'totalRetailValuation',
            'lowStockCount',
            'outOfStockCount',
            'inStockCount',
            'totalProducts',
            'categories',
            'allProducts'
        ));
    }

    /**
     * Handle stock adjustments (Add, Remove, or Set exact count).
     */
    public function adjust(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'mode' => 'required|in:add,remove,set',
            'quantity' => 'required|integer|min:1',
            'type' => 'nullable|string|in:in,out,damage,loss,adjustment,return',
            'reason' => 'required|string|max:255',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        try {
            if ($validated['mode'] === 'add') {
                $type = $validated['type'] ?? 'in';
                $movement = $this->inventoryService->addStock(
                    product: $product,
                    quantity: (int) $validated['quantity'],
                    reason: $validated['reason'],
                    type: $type
                );
                $msg = "Added {$validated['quantity']} units to {$product->name}. New stock: {$movement->stock_after}.";
            } elseif ($validated['mode'] === 'remove') {
                $type = $validated['type'] ?? 'out';
                $movement = $this->inventoryService->removeStock(
                    product: $product,
                    quantity: (int) $validated['quantity'],
                    reason: $validated['reason'],
                    type: $type
                );
                $msg = "Deducted {$validated['quantity']} units from {$product->name}. New stock: {$movement->stock_after}.";
            } else {
                // Exact count set
                $movement = $this->inventoryService->setStock(
                    product: $product,
                    newQuantity: (int) $validated['quantity'],
                    reason: $validated['reason']
                );
                $msg = "Stock count for {$product->name} updated to {$movement->stock_after}.";
            }

            app(NotificationService::class)->checkProductStockAlert($product, $movement->stock_after);
            ActivityLoggerService::log('inventory.adjust', "adjusted inventory for '{$product->name}' ({$msg})", $product, ['stock' => $movement->stock_after], 'inventory');

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'stock_movement' => $movement,
                    'new_stock' => $movement->stock_after,
                ]);
            }

            return back()->with('success', $msg);

        } catch (InvalidArgumentException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Quick addition of stock directly from product row.
     */
    public function quickAdd(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $reason = $validated['reason'] ?? 'Quick stock addition';

        $movement = $this->inventoryService->addStock(
            product: $product,
            quantity: (int) $validated['quantity'],
            reason: $reason,
            type: 'in'
        );

        $msg = "Added {$validated['quantity']} units to {$product->name}. New stock: {$movement->stock_after}.";
        app(NotificationService::class)->checkProductStockAlert($product, $movement->stock_after);
        ActivityLoggerService::log('inventory.quick_add', "quick added {$validated['quantity']} units to '{$product->name}'", $product, ['stock' => $movement->stock_after], 'inventory');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'new_stock' => $movement->stock_after,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Quick removal of stock directly from product row.
     */
    public function quickRemove(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $reason = $validated['reason'] ?? 'Quick stock reduction';

        try {
            $movement = $this->inventoryService->removeStock(
                product: $product,
                quantity: (int) $validated['quantity'],
                reason: $reason,
                type: 'out'
            );

            $msg = "Deducted {$validated['quantity']} units from {$product->name}. New stock: {$movement->stock_after}.";
            app(NotificationService::class)->checkProductStockAlert($product, $movement->stock_after);
            ActivityLoggerService::log('inventory.quick_remove', "quick removed {$validated['quantity']} units from '{$product->name}'", $product, ['stock' => $movement->stock_after], 'inventory');

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'new_stock' => $movement->stock_after,
                ]);
            }

            return back()->with('success', $msg);

        } catch (InvalidArgumentException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display stock movement audit history.
     */
    public function history(Request $request): View|JsonResponse
    {
        $filters = $request->only(['product_id', 'type', 'date_from', 'date_to', 'search']);

        $transactions = StockMovement::with(['product.category', 'user'])
            ->filter($filters)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $products = Product::select('id', 'name', 'sku')->orderBy('name')->get();
        $types = [
            'in' => 'Stock Addition (In)',
            'out' => 'Stock Removal (Out)',
            'adjustment' => 'Manual Adjustment',
            'damage' => 'Damaged Goods',
            'loss' => 'Lost / Discrepancy',
            'return' => 'Customer Return',
            'sale' => 'POS Sale Order',
            'purchase' => 'Vendor Procurement',
        ];

        if ($request->ajax() && $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'transactions' => $transactions,
            ]);
        }

        return view('inventory.history', compact('transactions', 'products', 'types'));
    }
}
