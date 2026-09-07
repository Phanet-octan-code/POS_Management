<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ReturnItem;
use App\Models\ReturnOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    /**
     * Resolve standardized date ranges from request:
     * 'today', 'yesterday', 'this_week', 'this_month', 'this_year', 'custom'.
     */
    public function resolveDateRange(Request $request): array
    {
        $dateFilter = $request->get('date_filter', 'this_month');

        switch ($dateFilter) {
            case 'today':
                $start = Carbon::today()->startOfDay();
                $end = Carbon::today()->endOfDay();
                $label = "Today (" . $start->format('M d, Y') . ")";
                break;

            case 'yesterday':
                $start = Carbon::yesterday()->startOfDay();
                $end = Carbon::yesterday()->endOfDay();
                $label = "Yesterday (" . $start->format('M d, Y') . ")";
                break;

            case 'this_week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                $label = "This Week (" . $start->format('M d') . " - " . $end->format('M d, Y') . ")";
                break;

            case 'this_year':
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                $label = "This Year (" . $start->year . ")";
                break;

            case 'custom':
                $start = $request->filled('start_date')
                    ? Carbon::parse($request->get('start_date'))->startOfDay()
                    : Carbon::now()->startOfMonth();
                $end = $request->filled('end_date')
                    ? Carbon::parse($request->get('end_date'))->endOfDay()
                    : Carbon::now()->endOfMonth();
                $label = "Custom (" . $start->format('M d, Y') . " - " . $end->format('M d, Y') . ")";
                break;

            case 'this_month':
            default:
                $dateFilter = 'this_month';
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                $label = "This Month (" . $start->format('F Y') . ")";
                break;
        }

        return [
            'date_filter' => $dateFilter,
            'start' => $start,
            'end' => $end,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'label' => $label,
        ];
    }

    /**
     * Get summary metrics for Dashboard and Analytics.
     */
    public function getDashboardMetrics(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::today()->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::today()->endOfDay();

        $todayGrossSales = (float) Sale::whereBetween('sale_date', [$start, $end])->sum('total_amount');
        $todayOrders = (int) Sale::whereBetween('sale_date', [$start, $end])->count();
        $todayExpenses = (float) Expense::whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])->sum('amount');
        $todayPurchases = (float) Purchase::whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])->sum('total_amount');

        $todayRefunds = (float) ReturnOrder::whereBetween('return_date', [$start, $end])
            ->where('status', 'completed')
            ->sum('total_refund');

        $netSales = max(0, $todayGrossSales - $todayRefunds);

        $costOfGoodsSold = (float) (SaleItem::whereHas('sale', function ($query) use ($start, $end) {
            $query->whereBetween('sale_date', [$start, $end]);
        })->join('products', 'sale_items.product_id', '=', 'products.id')
          ->selectRaw('SUM(sale_items.quantity * COALESCE(NULLIF(sale_items.cost_price, 0), products.cost_price, 0)) as cogs')
          ->value('cogs') ?? 0);

        $returnedCogs = (float) (ReturnItem::whereHas('returnOrder', function ($query) use ($start, $end) {
            $query->whereBetween('return_date', [$start, $end])->where('status', 'completed');
        })->join('products', 'return_items.product_id', '=', 'products.id')
          ->selectRaw('SUM(return_items.quantity * products.cost_price) as rcogs')
          ->value('rcogs') ?? 0);

        $netCogs = max(0, $costOfGoodsSold - $returnedCogs);
        $grossProfit = $netSales - $netCogs;
        $netProfit = $grossProfit - $todayExpenses;

        return [
            'total_sales' => $todayGrossSales,
            'total_orders' => $todayOrders,
            'total_refunds' => $todayRefunds,
            'net_sales' => $netSales,
            'total_expenses' => $todayExpenses,
            'total_purchases' => $todayPurchases,
            'cogs' => $costOfGoodsSold,
            'net_cogs' => $netCogs,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * 1. SALES REPORT
     */
    public function getSalesReport(array $dateRange, array $params, bool $exportAll = false): array
    {
        $query = Sale::with(['customer', 'user', 'items.product'])
            ->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']]);

        // Search Filter
        if (!empty($params['search'])) {
            $s = trim($params['search']);
            $query->where(function ($q) use ($s) {
                $q->where('invoice_no', 'like', "%{$s}%")
                  ->orWhere('payment_method', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"))
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$s}%"));
            });
        }

        // Status filter
        if (!empty($params['payment_status'])) {
            $query->where('payment_status', $params['payment_status']);
        }

        // Payment method filter
        if (!empty($params['payment_method'])) {
            $query->where('payment_method', $params['payment_method']);
        }

        // Sorting
        $sortBy = $params['sort_by'] ?? 'sale_date';
        $sortDir = strtolower($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['invoice_no', 'sale_date', 'total_amount', 'subtotal', 'discount_amount', 'tax_amount'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest('sale_date');
        }

        // Summary Statistics (calculated over entire filtered dataset)
        $summary = [
            'total_sales' => (float) (clone $query)->sum('total_amount'),
            'total_invoices' => (int) (clone $query)->count(),
            'total_tax' => (float) (clone $query)->sum('tax_amount'),
            'total_discount' => (float) (clone $query)->sum('discount_amount'),
            'avg_order_value' => (clone $query)->count() > 0 ? (float) (clone $query)->avg('total_amount') : 0.0,
        ];

        $records = $exportAll ? $query->get() : $query->paginate($params['per_page'] ?? 15)->withQueryString();

        return [
            'records' => $records,
            'summary' => $summary,
        ];
    }

    /**
     * 2. PURCHASE REPORT
     */
    public function getPurchaseReport(array $dateRange, array $params, bool $exportAll = false): array
    {
        $query = Purchase::with(['supplier', 'user', 'items.product'])
            ->whereBetween('purchase_date', [$dateRange['start_date'], $dateRange['end_date']]);

        // Search Filter
        if (!empty($params['search'])) {
            $s = trim($params['search']);
            $query->where(function ($q) use ($s) {
                $q->where('reference_no', 'like', "%{$s}%")
                  ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', "%{$s}%")->orWhere('company_name', 'like', "%{$s}%"));
            });
        }

        if (!empty($params['payment_status'])) {
            $query->where('payment_status', $params['payment_status']);
        }

        // Sorting
        $sortBy = $params['sort_by'] ?? 'purchase_date';
        if ($sortBy === 'purchase_number') {
            $sortBy = 'reference_no';
        }
        $sortDir = strtolower($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['reference_no', 'purchase_date', 'total_amount', 'paid_amount', 'due_amount'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest('purchase_date');
        }

        $summary = [
            'total_purchases' => (float) (clone $query)->sum('total_amount'),
            'total_orders' => (int) (clone $query)->count(),
            'total_paid' => (float) (clone $query)->sum('paid_amount'),
            'total_due' => (float) (clone $query)->sum('due_amount'),
        ];

        $records = $exportAll ? $query->get() : $query->paginate($params['per_page'] ?? 15)->withQueryString();

        return [
            'records' => $records,
            'summary' => $summary,
        ];
    }

    /**
     * 3. PROFIT REPORT
     * Gross Profit = Sales Revenue - Product Cost (COGS)
     * Net Profit = Gross Profit - Expenses
     */
    public function getProfitReport(array $dateRange, array $params, bool $exportAll = false): array
    {
        $query = Sale::with(['customer', 'user', 'items.product'])
            ->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']]);

        if (!empty($params['search'])) {
            $s = trim($params['search']);
            $query->where(function ($q) use ($s) {
                $q->where('invoice_no', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$s}%"));
            });
        }

        $sortBy = $params['sort_by'] ?? 'sale_date';
        $sortDir = strtolower($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['invoice_no', 'sale_date', 'total_amount'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest('sale_date');
        }

        // Total Expenses in this specific date range
        $totalExpenses = (float) Expense::whereBetween('expense_date', [
            $dateRange['start_date'],
            $dateRange['end_date']
        ])->sum('amount');

        // Total Refunds in this date range
        $totalRefunds = (float) ReturnOrder::whereBetween('return_date', [
            $dateRange['start'],
            $dateRange['end']
        ])->where('status', 'completed')->sum('total_refund');

        // All-sales totals
        $allSales = (clone $query)->get();
        $totalRevenue = 0.0;
        $totalCogs = 0.0;

        foreach ($allSales as $sale) {
            $saleRevenue = (float) $sale->total_amount;
            $saleCogs = 0.0;
            foreach ($sale->items as $item) {
                $cost = (float) ($item->cost_price ?: ($item->product?->cost_price ?? 0));
                $saleCogs += ($item->quantity * $cost);
            }
            $totalRevenue += $saleRevenue;
            $totalCogs += $saleCogs;
        }

        $netRevenue = max(0, $totalRevenue - $totalRefunds);
        $grossProfit = $netRevenue - $totalCogs;
        $netProfit = $grossProfit - $totalExpenses;
        $grossMargin = $netRevenue > 0 ? ($grossProfit / $netRevenue) * 100 : 0.0;
        $netMargin = $netRevenue > 0 ? ($netProfit / $netRevenue) * 100 : 0.0;

        $summary = [
            'total_revenue' => $totalRevenue,
            'total_refunds' => $totalRefunds,
            'net_revenue' => $netRevenue,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'gross_margin' => $grossMargin,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
            'net_margin' => $netMargin,
        ];

        // Paginate sales and compute each sale's individual Gross Profit
        $records = $exportAll ? $query->get() : $query->paginate($params['per_page'] ?? 15)->withQueryString();

        foreach ($records as $sale) {
            $saleRevenue = (float) $sale->total_amount;
            $saleCogs = 0.0;
            $itemsCount = 0;
            foreach ($sale->items as $item) {
                $cost = (float) ($item->cost_price ?: ($item->product?->cost_price ?? 0));
                $saleCogs += ($item->quantity * $cost);
                $itemsCount += $item->quantity;
            }
            $saleGross = $saleRevenue - $saleCogs;
            $saleMargin = $saleRevenue > 0 ? ($saleGross / $saleRevenue) * 100 : 0.0;

            $sale->items_count_total = $itemsCount;
            $sale->calculated_cogs = $saleCogs;
            $sale->calculated_gross_profit = $saleGross;
            $sale->calculated_margin = $saleMargin;
        }

        return [
            'records' => $records,
            'summary' => $summary,
        ];
    }

    /**
     * 4. INVENTORY REPORT
     */
    public function getInventoryReport(array $params, bool $exportAll = false): array
    {
        $query = Product::with(['category', 'brand', 'supplier']);

        if (!empty($params['search'])) {
            $s = trim($params['search']);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('sku', 'like', "%{$s}%")
                  ->orWhere('barcode', 'like', "%{$s}%");
            });
        }

        if (!empty($params['category_id'])) {
            $query->where('category_id', $params['category_id']);
        }

        if (!empty($params['stock_status'])) {
            if ($params['stock_status'] === 'low_stock') {
                $query->whereColumn('stock_quantity', '<=', 'alert_quantity')->where('stock_quantity', '>', 0);
            } elseif ($params['stock_status'] === 'out_of_stock') {
                $query->where('stock_quantity', '<=', 0);
            } elseif ($params['stock_status'] === 'in_stock') {
                $query->whereColumn('stock_quantity', '>', 'alert_quantity');
            }
        }

        $sortBy = $params['sort_by'] ?? 'stock_quantity';
        $sortDir = strtolower($params['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if (in_array($sortBy, ['name', 'sku', 'stock_quantity', 'cost_price', 'selling_price', 'alert_quantity'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('stock_quantity', 'asc');
        }

        // Summary stats across entire catalogue
        $all = (clone $query)->get();
        $totalItems = $all->count();
        $totalUnits = (int) $all->sum('stock_quantity');
        $totalCostValue = 0.0;
        $totalRetailValue = 0.0;

        foreach ($all as $p) {
            $qty = max(0, $p->stock_quantity);
            $totalCostValue += ($qty * (float) $p->cost_price);
            $totalRetailValue += ($qty * (float) $p->selling_price);
        }

        $potentialProfit = $totalRetailValue - $totalCostValue;

        $summary = [
            'total_items' => $totalItems,
            'total_units' => $totalUnits,
            'total_cost_value' => $totalCostValue,
            'total_retail_value' => $totalRetailValue,
            'potential_profit' => $potentialProfit,
        ];

        $records = $exportAll ? $query->get() : $query->paginate($params['per_page'] ?? 15)->withQueryString();

        return [
            'records' => $records,
            'summary' => $summary,
        ];
    }

    /**
     * 5. CUSTOMER REPORT
     */
    public function getCustomerReport(array $dateRange, array $params, bool $exportAll = false): array
    {
        $query = Customer::withCount(['sales' => function ($q) use ($dateRange) {
            $q->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']]);
        }])->withSum(['sales' => function ($q) use ($dateRange) {
            $q->whereBetween('sale_date', [$dateRange['start'], $dateRange['end']]);
        }], 'total_amount');

        if (!empty($params['search'])) {
            $s = trim($params['search']);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if (!empty($params['customer_type'])) {
            $query->where('customer_type', $params['customer_type']);
        }

        $sortBy = $params['sort_by'] ?? 'sales_sum_total_amount';
        $sortDir = strtolower($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['name', 'phone', 'balance', 'sales_count', 'sales_sum_total_amount', 'total_spent'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderByDesc('sales_sum_total_amount');
        }

        $all = (clone $query)->get();
        $summary = [
            'total_customers' => $all->count(),
            'total_orders' => (int) $all->sum('sales_count'),
            'total_revenue' => (float) $all->sum('sales_sum_total_amount'),
            'total_receivables' => (float) $all->sum('balance'),
        ];

        $records = $exportAll ? $query->get() : $query->paginate($params['per_page'] ?? 15)->withQueryString();

        return [
            'records' => $records,
            'summary' => $summary,
        ];
    }

    /**
     * 6. SUPPLIER REPORT
     */
    public function getSupplierReport(array $dateRange, array $params, bool $exportAll = false): array
    {
        $query = Supplier::withCount(['purchases' => function ($q) use ($dateRange) {
            $q->whereBetween('purchase_date', [$dateRange['start_date'], $dateRange['end_date']]);
        }])->withSum(['purchases' => function ($q) use ($dateRange) {
            $q->whereBetween('purchase_date', [$dateRange['start_date'], $dateRange['end_date']]);
        }], 'total_amount');

        if (!empty($params['search'])) {
            $s = trim($params['search']);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('company_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $sortBy = $params['sort_by'] ?? 'purchases_sum_total_amount';
        if ($sortBy === 'company') {
            $sortBy = 'company_name';
        }
        $sortDir = strtolower($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['name', 'company_name', 'balance', 'purchases_count', 'purchases_sum_total_amount'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderByDesc('purchases_sum_total_amount');
        }

        $all = (clone $query)->get();
        $summary = [
            'total_suppliers' => $all->count(),
            'total_orders' => (int) $all->sum('purchases_count'),
            'total_purchased' => (float) $all->sum('purchases_sum_total_amount'),
            'total_payables' => (float) $all->sum('balance'),
        ];

        $records = $exportAll ? $query->get() : $query->paginate($params['per_page'] ?? 15)->withQueryString();

        return [
            'records' => $records,
            'summary' => $summary,
        ];
    }

    /**
     * 7. EXPENSE REPORT
     */
    public function getExpenseReport(array $dateRange, array $params, bool $exportAll = false): array
    {
        $query = Expense::with(['category', 'user'])
            ->whereBetween('expense_date', [$dateRange['start_date'], $dateRange['end_date']]);

        if (!empty($params['search'])) {
            $s = trim($params['search']);
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('reference_no', 'like', "%{$s}%")
                  ->orWhere('notes', 'like', "%{$s}%")
                  ->orWhereHas('category', fn($cq) => $cq->where('name', 'like', "%{$s}%"))
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$s}%"));
            });
        }

        if (!empty($params['category_id'])) {
            $query->where('expense_category_id', $params['category_id']);
        }

        $sortBy = $params['sort_by'] ?? 'expense_date';
        $sortDir = strtolower($params['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['title', 'reference_no', 'expense_date', 'amount'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->latest('expense_date')->latest('id');
        }

        $all = (clone $query)->get();
        $totalAmount = (float) $all->sum('amount');
        $totalCount = $all->count();
        $avgExpense = $totalCount > 0 ? $totalAmount / $totalCount : 0.0;

        // Category Breakdown
        $categoryBreakdown = $all->groupBy('expense_category_id')->map(function ($items) {
            return [
                'category_name' => $items->first()->category?->name ?? 'Uncategorized',
                'category_color' => $items->first()->category_color ?? 'primary',
                'count' => $items->count(),
                'total' => (float) $items->sum('amount'),
            ];
        })->sortByDesc('total')->values();

        $summary = [
            'total_expenses' => $totalAmount,
            'total_count' => $totalCount,
            'avg_expense' => $avgExpense,
            'category_breakdown' => $categoryBreakdown,
        ];

        $records = $exportAll ? $query->get() : $query->paginate($params['per_page'] ?? 15)->withQueryString();

        return [
            'records' => $records,
            'summary' => $summary,
        ];
    }

    /**
     * EXPORT TO CSV
     */
    public function exportCsv(array $headers, array $rows, string $filename): StreamedResponse
    {
        $callback = function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * EXPORT TO EXCEL (.xls spreadsheet format)
     */
    public function exportExcel(string $view, array $data, string $filename): Response
    {
        $content = view($view, $data)->render();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * EXPORT TO PDF (via DomPDF)
     */
    public function exportPdf(string $view, array $data, string $filename, string $orientation = 'landscape'): Response
    {
        $data['store_name'] = Setting::get('store_name', 'OmniPOS Superstore');
        $data['store_address'] = Setting::get('store_address', '100 Downtown Boulevard, Metropolis');
        $data['store_phone'] = Setting::get('store_phone', '+1 (555) 019-2831');
        $data['printed_at'] = now()->format('M d, Y H:i:s');

        $pdf = Pdf::loadView($view, $data);
        $pdf->setPaper('a4', $orientation);

        return $pdf->download($filename);
    }
}
