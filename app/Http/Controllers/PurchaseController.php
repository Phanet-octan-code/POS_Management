<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\ActivityLoggerService;
use App\Services\NotificationService;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {}

    /**
     * Display a listing of purchases with financial KPI cards and search filters.
     */
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only(['search', 'supplier_id', 'status', 'payment_status', 'date_from', 'date_to']);

        $query = Purchase::with(['supplier', 'user', 'items.product'])->filter($filters);

        $purchases = $query->latest()->paginate(15)->withQueryString();

        // Financial KPI Metrics
        $totalPurchasesCount = Purchase::count();
        $totalPurchasesAmount = (float) Purchase::sum('total_amount');
        $totalPaidAmount = (float) Purchase::sum('paid_amount');
        $totalDueAmount = (float) Purchase::sum('due_amount');

        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        if ($request->ajax() && $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'purchases' => $purchases,
                'kpi' => [
                    'total_count' => $totalPurchasesCount,
                    'total_amount' => $totalPurchasesAmount,
                    'paid_amount' => $totalPaidAmount,
                    'due_amount' => $totalDueAmount,
                ],
            ]);
        }

        return view('purchases.index', compact(
            'purchases',
            'totalPurchasesCount',
            'totalPurchasesAmount',
            'totalPaidAmount',
            'totalDueAmount',
            'suppliers'
        ));
    }

    /**
     * Show the form for creating a new purchase.
     */
    public function create(): View
    {
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->with('category')->orderBy('name')->get();
        $generatedRefNo = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        return view('purchases.create', compact('suppliers', 'products', 'generatedRefNo'));
    }

    /**
     * Store a newly created purchase in storage.
     */
    public function store(PurchaseRequest $request): RedirectResponse|JsonResponse
    {
        $userId = Auth::id() ?? 1;
        $purchase = $this->purchaseService->createPurchase($request->validated(), $userId);

        app(NotificationService::class)->notifyNewPurchase($purchase);

        ActivityLoggerService::log(
            action: 'purchase.create',
            description: "created purchase order {$purchase->purchase_number}",
            subject: $purchase,
            properties: ['amount' => $purchase->total_amount, 'supplier' => $purchase->supplier?->name],
            module: 'purchases'
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Purchase order '{$purchase->purchase_number}' recorded successfully.",
                'purchase' => $purchase,
            ]);
        }

        return redirect()->route('purchases.show', $purchase)->with(
            'success',
            "Purchase order '{$purchase->purchase_number}' created successfully."
        );
    }

    /**
     * Display the specified purchase details.
     */
    public function show(Purchase $purchase): View|JsonResponse
    {
        $purchase->load(['supplier', 'user', 'items.product.category']);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'purchase' => $purchase,
            ]);
        }

        return view('purchases.show', compact('purchase'));
    }

    /**
     * Show the form for editing the specified purchase.
     */
    public function edit(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'items.product']);
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->with('category')->orderBy('name')->get();

        return view('purchases.edit', compact('purchase', 'suppliers', 'products'));
    }

    /**
     * Update the specified purchase in storage.
     */
    public function update(PurchaseRequest $request, Purchase $purchase): RedirectResponse|JsonResponse
    {
        $userId = Auth::id() ?? 1;
        $updatedPurchase = $this->purchaseService->updatePurchase($purchase, $request->validated(), $userId);

        ActivityLoggerService::log(
            action: 'purchase.update',
            description: "updated purchase order {$updatedPurchase->purchase_number}",
            subject: $updatedPurchase,
            properties: ['amount' => $updatedPurchase->total_amount],
            module: 'purchases'
        );

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Purchase order '{$updatedPurchase->purchase_number}' updated successfully.",
                'purchase' => $updatedPurchase,
            ]);
        }

        return redirect()->route('purchases.show', $updatedPurchase)->with(
            'success',
            "Purchase order '{$updatedPurchase->purchase_number}' updated successfully."
        );
    }

    /**
     * Remove the specified purchase from storage.
     */
    public function destroy(Purchase $purchase): RedirectResponse|JsonResponse
    {
        $ref = $purchase->purchase_number;
        $userId = Auth::id() ?? 1;

        $this->purchaseService->deletePurchase($purchase, $userId);

        ActivityLoggerService::log(
            action: 'purchase.delete',
            description: "deleted purchase order {$ref}",
            module: 'purchases'
        );

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Purchase order '{$ref}' deleted and stock reverted.",
            ]);
        }

        return redirect()->route('purchases.index')->with(
            'success',
            "Purchase order '{$ref}' deleted successfully and stock reverted."
        );
    }

    /**
     * Generate a printable purchase invoice / voucher.
     */
    public function invoice(Purchase $purchase): View
    {
        $purchase->load(['supplier', 'user', 'items.product.category']);
        return view('purchases.invoice', compact('purchase'));
    }
}
