<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Models\Customer;
use App\Services\ActivityLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filters = $request->only(['search', 'type', 'status']);

        $query = Customer::withCount('sales')->filter($filters);

        $customers = $query->latest()->paginate(15)->withQueryString();

        // Metrics
        $totalCustomers = Customer::count();
        $totalVip = Customer::where('type', 'VIP')->count();
        $totalWholesale = Customer::where('type', 'Wholesale')->count();
        $totalReceivableBalance = Customer::sum('balance');

        if ($request->ajax() && $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'customers' => $customers,
            ]);
        }

        return view('customers.index', compact('customers', 'totalCustomers', 'totalVip', 'totalWholesale', 'totalReceivableBalance'));
    }

    public function store(CustomerRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['balance'] = $data['balance'] ?? 0;

        $customer = Customer::create($data);
        ActivityLoggerService::log('customer.create', "created customer '{$customer->name}'", $customer, [], 'customers');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'customer' => $customer,
                'message' => "Customer '{$customer->name}' created successfully.",
            ]);
        }

        return back()->with('success', "Customer '{$customer->name}' created successfully.");
    }

    public function show(Customer $customer, Request $request): View|JsonResponse
    {
        $customer->load(['sales' => function ($q) {
            $q->latest()->take(20)->with(['items.product', 'user']);
        }, 'payments' => function ($q) {
            $q->latest()->take(10);
        }, 'returns' => function ($q) {
            $q->latest()->take(10);
        }]);

        $totalPurchasesAmount = $customer->sales()->sum('total_amount');
        $totalOrdersCount = $customer->sales()->count();
        $totalReturnsCount = $customer->returns()->count();

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'customer' => $customer,
                'total_purchases_amount' => number_format($totalPurchasesAmount, 2),
                'total_orders_count' => $totalOrdersCount,
                'total_returns_count' => $totalReturnsCount,
            ]);
        }

        return view('customers.show', compact('customer', 'totalPurchasesAmount', 'totalOrdersCount', 'totalReturnsCount'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $customer->update($data);
        ActivityLoggerService::log('customer.update', "updated customer '{$customer->name}'", $customer, [], 'customers');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'customer' => $customer,
                'message' => "Customer '{$customer->name}' updated successfully.",
            ]);
        }

        return back()->with('success', "Customer '{$customer->name}' updated successfully.");
    }

    public function toggleStatus(Customer $customer): JsonResponse|RedirectResponse
    {
        $customer->is_active = !$customer->is_active;
        $customer->save();

        $statusStr = $customer->is_active ? 'activated' : 'deactivated';
        ActivityLoggerService::log('customer.status_updated', "{$statusStr} customer '{$customer->name}'", $customer, [], 'customers');

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => $customer->is_active,
                'message' => "Customer '{$customer->name}' {$statusStr}.",
            ]);
        }

        return back()->with('success', "Customer '{$customer->name}' {$statusStr}.");
    }

    public function destroy(Customer $customer): RedirectResponse|JsonResponse
    {
        $name = $customer->name;

        // Check if customer has associated sales orders
        if ($customer->sales()->count() > 0) {
            $customer->delete(); // Soft delete protects historic invoices
            $msg = "Customer '{$name}' was archived (soft-deleted) to preserve transaction history.";
        } else {
            $customer->forceDelete();
            $msg = "Customer '{$name}' deleted successfully.";
        }

        ActivityLoggerService::log('customer.delete', "deleted customer '{$name}'", null, [], 'customers');

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }
}
