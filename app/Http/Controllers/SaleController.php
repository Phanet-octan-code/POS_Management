<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    /**
     * Display a filtered list of POS sales with search, date range, and status filters.
     */
    public function index(Request $request): View
    {
        $query = Sale::with(['customer', 'user', 'items']);

        // 1. Text Search (Invoice Number, Customer Name, Customer Phone, Cashier Name)
        if ($search = trim($request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Date Filter (start_date, end_date)
        if ($startDate = $request->get('start_date')) {
            $query->whereDate('sale_date', '>=', $startDate);
        }

        if ($endDate = $request->get('end_date')) {
            $query->whereDate('sale_date', '<=', $endDate);
        }

        // 3. Payment Status Filter (paid, partial, unpaid)
        if ($paymentStatus = $request->get('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        // 4. Payment Method Filter
        if ($paymentMethod = $request->get('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        // Summary Aggregates (filtered)
        $totalSalesCount = (clone $query)->count();
        $totalRevenue = (float) (clone $query)->sum('total_amount');
        $totalPaid = (float) (clone $query)->sum('paid_amount');
        $totalDue = (float) (clone $query)->sum('due_amount');

        $sales = $query->latest('sale_date')->paginate(15)->withQueryString();

        return view('sales.index', compact(
            'sales',
            'totalSalesCount',
            'totalRevenue',
            'totalPaid',
            'totalDue'
        ));
    }

    /**
     * Display sale details, breakdown, and return status.
     */
    public function show(Sale $sale): View
    {
        $sale->load(['customer', 'user', 'items.product', 'returns.items']);
        return view('sales.show', compact('sale'));
    }
}
