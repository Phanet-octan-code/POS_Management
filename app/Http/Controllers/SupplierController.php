<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use App\Services\ActivityLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only(['search', 'status']);

        $query = Supplier::withCount(['purchases', 'products'])->filter($filters);

        $suppliers = $query->latest()->paginate(15)->withQueryString();

        // Metrics
        $totalSuppliers = Supplier::count();
        $activeSuppliers = Supplier::where('is_active', true)->count();
        $totalPayableBalance = Supplier::sum('balance');

        if ($request->ajax() && $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'suppliers' => $suppliers,
            ]);
        }

        return view('suppliers.index', compact('suppliers', 'totalSuppliers', 'activeSuppliers', 'totalPayableBalance'));
    }

    public function store(SupplierRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['balance'] = $data['balance'] ?? 0;

        $supplier = Supplier::create($data);
        ActivityLoggerService::log('supplier.create', "created supplier '{$supplier->name}'", $supplier, [], 'suppliers');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'supplier' => $supplier,
                'message' => "Supplier '{$supplier->name}' created successfully.",
            ]);
        }

        return back()->with('success', "Supplier '{$supplier->name}' created successfully.");
    }

    public function show(Supplier $supplier, Request $request): View|JsonResponse
    {
        $supplier->load(['purchases' => function ($q) {
            $q->latest()->take(20)->with(['items.product', 'user']);
        }, 'products' => function ($q) {
            $q->take(15)->with('category');
        }, 'payments' => function ($q) {
            $q->latest()->take(10);
        }]);

        $totalPurchasesAmount = $supplier->purchases()->sum('total_amount');
        $totalPurchasesCount = $supplier->purchases()->count();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'supplier' => $supplier,
                'total_purchases_amount' => number_format($totalPurchasesAmount, 2),
                'total_purchases_count' => $totalPurchasesCount,
            ]);
        }

        return view('suppliers.show', compact('supplier', 'totalPurchasesAmount', 'totalPurchasesCount'));
    }

    public function update(SupplierRequest $request, Supplier $supplier): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $supplier->update($data);
        ActivityLoggerService::log('supplier.update', "updated supplier '{$supplier->name}'", $supplier, [], 'suppliers');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'supplier' => $supplier,
                'message' => "Supplier '{$supplier->name}' updated successfully.",
            ]);
        }

        return back()->with('success', "Supplier '{$supplier->name}' updated successfully.");
    }

    public function toggleStatus(Supplier $supplier): JsonResponse|RedirectResponse
    {
        $supplier->is_active = !$supplier->is_active;
        $supplier->save();

        $statusStr = $supplier->is_active ? 'activated' : 'deactivated';
        ActivityLoggerService::log('supplier.status_updated', "{$statusStr} supplier '{$supplier->name}'", $supplier, [], 'suppliers');

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => $supplier->is_active,
                'message' => "Supplier '{$supplier->name}' {$statusStr}.",
            ]);
        }

        return back()->with('success', "Supplier '{$supplier->name}' {$statusStr}.");
    }

    public function destroy(Supplier $supplier): RedirectResponse|JsonResponse
    {
        $name = $supplier->name;

        // Check if supplier has associated purchases
        if ($supplier->purchases()->count() > 0) {
            $supplier->delete(); // Soft delete protects historic invoices
            $msg = "Supplier '{$name}' was archived (soft-deleted) to preserve purchase transaction history.";
        } else {
            $supplier->forceDelete();
            $msg = "Supplier '{$name}' deleted successfully.";
        }

        ActivityLoggerService::log('supplier.delete', "deleted supplier '{$name}'", null, [], 'suppliers');

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }
}
