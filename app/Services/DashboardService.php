<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\ReturnOrder;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Compute all 12 summary cards directly from MySQL tables.
     */
    public function getSummaryCards(): array
    {
        // 1. Sales & Orders
        $totalSales = (float) Sale::sum('total_amount');
        $todaySales = (float) Sale::whereDate('sale_date', Carbon::today())->sum('total_amount');
        $monthlySales = (float) Sale::whereBetween('sale_date', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ])->sum('total_amount');
        $totalOrders = (int) Sale::count();

        // 2. Inventory Health
        $totalProducts = (int) Product::count();
        $lowStock = (int) Product::whereColumn('stock_quantity', '<=', 'alert_quantity')
            ->where('stock_quantity', '>', 0)
            ->count();
        $outOfStock = (int) Product::where('stock_quantity', '<=', 0)->count();

        // 3. Customers & Suppliers
        $totalCustomers = (int) Customer::count();
        $totalSuppliers = (int) Supplier::count();

        // 4. Expenses & Profitability
        $totalExpenses = (float) Expense::sum('amount');

        // Customer refunds all-time
        $totalRefunds = (float) ReturnOrder::where('status', 'completed')->sum('total_refund');
        $netSales = max(0, $totalSales - $totalRefunds);

        // All-time COGS for items sold
        $costOfGoodsSold = (float) (SaleItem::join('products', 'sale_items.product_id', '=', 'products.id')
            ->selectRaw('SUM(sale_items.quantity * COALESCE(NULLIF(sale_items.cost_price, 0), products.cost_price, 0)) as cogs')
            ->value('cogs') ?? 0);

        // COGS adjustment for returned items restored to inventory
        $returnedCogs = (float) (ReturnItem::whereHas('returnOrder', fn($q) => $q->where('status', 'completed'))
            ->join('products', 'return_items.product_id', '=', 'products.id')
            ->selectRaw('SUM(return_items.quantity * products.cost_price) as rcogs')
            ->value('rcogs') ?? 0);

        $netCogs = max(0, $costOfGoodsSold - $returnedCogs);
        $grossProfit = $netSales - $netCogs;
        $netProfit = $grossProfit - $totalExpenses;

        return [
            'total_sales' => $totalSales,
            'today_sales' => $todaySales,
            'monthly_sales' => $monthlySales,
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'total_customers' => $totalCustomers,
            'total_suppliers' => $totalSuppliers,
            'total_expenses' => $totalExpenses,
            'gross_profit' => $grossProfit,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Get Sales by Day / Trend chart data based on filter:
     * 'today', '7days', '30days', 'this_year'.
     */
    public function getSalesTrendChart(string $filter = '7days'): array
    {
        $labels = [];
        $data = [];
        $title = 'Sales Trend';

        switch ($filter) {
            case 'today':
                $title = "Today's Sales by Hour (" . Carbon::today()->format('M d, Y') . ")";
                // 2-hour interval buckets from 00:00 to 24:00
                for ($hour = 0; $hour < 24; $hour += 2) {
                    $startHour = Carbon::today()->copy()->addHours($hour);
                    $endHour = Carbon::today()->copy()->addHours($hour + 2);
                    $hourLabel = $startHour->format('H:i') . '-' . $endHour->format('H:i');

                    $amount = (float) Sale::whereBetween('sale_date', [$startHour, $endHour])
                        ->sum('total_amount');

                    $labels[] = $hourLabel;
                    $data[] = $amount;
                }
                break;

            case '30days':
                $title = "Daily Sales (Last 30 Days)";
                for ($i = 29; $i >= 0; $i--) {
                    $date = Carbon::today()->subDays($i);
                    $labels[] = $date->format('M d');
                    $amount = (float) Sale::whereDate('sale_date', $date->toDateString())->sum('total_amount');
                    $data[] = $amount;
                }
                break;

            case 'this_year':
                $title = "Monthly Sales (" . Carbon::now()->year . ")";
                for ($m = 1; $m <= 12; $m++) {
                    $monthDate = Carbon::create(Carbon::now()->year, $m, 1);
                    $labels[] = $monthDate->format('M');
                    $amount = (float) Sale::whereYear('sale_date', Carbon::now()->year)
                        ->whereMonth('sale_date', $m)
                        ->sum('total_amount');
                    $data[] = $amount;
                }
                break;

            case '7days':
            default:
                $title = "Daily Sales (Last 7 Days)";
                for ($i = 6; $i >= 0; $i--) {
                    $date = Carbon::today()->subDays($i);
                    $labels[] = $date->format('M d');
                    $amount = (float) Sale::whereDate('sale_date', $date->toDateString())->sum('total_amount');
                    $data[] = $amount;
                }
                break;
        }

        return [
            'title' => $title,
            'labels' => $labels,
            'data' => $data,
            'total' => array_sum($data),
            'filter' => $filter,
        ];
    }

    /**
     * Get Sales by Month for current calendar year (12 months).
     */
    public function getMonthlySalesChart(): array
    {
        $year = Carbon::now()->year;
        $labels = [];
        $data = [];

        for ($m = 1; $m <= 12; $m++) {
            $monthDate = Carbon::create($year, $m, 1);
            $labels[] = $monthDate->format('M');

            $amount = (float) Sale::whereYear('sale_date', $year)
                ->whereMonth('sale_date', $m)
                ->sum('total_amount');

            $data[] = $amount;
        }

        return [
            'year' => $year,
            'labels' => $labels,
            'data' => $data,
            'total_year' => array_sum($data),
        ];
    }

    /**
     * Get Profit by Month (Gross Profit and Net Profit) for current calendar year.
     */
    public function getMonthlyProfitChart(): array
    {
        $year = Carbon::now()->year;
        $labels = [];
        $grossProfits = [];
        $netProfits = [];
        $expenses = [];

        for ($m = 1; $m <= 12; $m++) {
            $monthStart = Carbon::create($year, $m, 1)->startOfMonth();
            $monthEnd = Carbon::create($year, $m, 1)->endOfMonth();
            $labels[] = $monthStart->format('M');

            // Sales in this month
            $monthSales = (float) Sale::whereBetween('sale_date', [$monthStart, $monthEnd])->sum('total_amount');
            $monthRefunds = (float) ReturnOrder::whereBetween('return_date', [$monthStart, $monthEnd])
                ->where('status', 'completed')
                ->sum('total_refund');
            $monthNetSales = max(0, $monthSales - $monthRefunds);

            // COGS in this month
            $monthCogs = (float) (SaleItem::whereHas('sale', function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('sale_date', [$monthStart, $monthEnd]);
            })->join('products', 'sale_items.product_id', '=', 'products.id')
              ->selectRaw('SUM(sale_items.quantity * COALESCE(NULLIF(sale_items.cost_price, 0), products.cost_price, 0)) as cogs')
              ->value('cogs') ?? 0);

            $monthReturnedCogs = (float) (ReturnItem::whereHas('returnOrder', function ($q) use ($monthStart, $monthEnd) {
                $q->whereBetween('return_date', [$monthStart, $monthEnd])->where('status', 'completed');
            })->join('products', 'return_items.product_id', '=', 'products.id')
              ->selectRaw('SUM(return_items.quantity * products.cost_price) as rcogs')
              ->value('rcogs') ?? 0);

            $monthNetCogs = max(0, $monthCogs - $monthReturnedCogs);
            $monthGrossProfit = $monthNetSales - $monthNetCogs;

            // Operating expenses in this month
            $monthExpenses = (float) Expense::whereBetween('expense_date', [
                $monthStart->toDateString(),
                $monthEnd->toDateString()
            ])->sum('amount');

            $monthNetProfit = $monthGrossProfit - $monthExpenses;

            $grossProfits[] = $monthGrossProfit;
            $netProfits[] = $monthNetProfit;
            $expenses[] = $monthExpenses;
        }

        return [
            'year' => $year,
            'labels' => $labels,
            'gross_profit' => $grossProfits,
            'net_profit' => $netProfits,
            'expenses' => $expenses,
        ];
    }

    /**
     * Get Recent Sales for dashboard table.
     * Fields: Invoice, Customer, Cashier, Total, Payment Status, Date.
     */
    public function getRecentSales(int $limit = 6): Collection
    {
        return Sale::with(['customer', 'user'])
            ->latest('sale_date')
            ->latest('id')
            ->take($limit)
            ->get();
    }

    /**
     * Get Top Selling Products for dashboard table.
     * Fields: Product (name, sku), Quantity Sold, Revenue.
     */
    public function getTopSellingProducts(int $limit = 6): Collection
    {
        return SaleItem::selectRaw('product_id, SUM(quantity) as total_qty, SUM(subtotal) as total_revenue')
            ->with(['product.category', 'product.brand'])
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take($limit)
            ->get();
    }

    /**
     * Get Low Stock & Out of Stock Products for dashboard table.
     * Fields: Product, SKU, Stock, Minimum Stock, Status.
     */
    public function getLowStockProducts(int $limit = 6): Collection
    {
        return Product::whereColumn('stock_quantity', '<=', 'alert_quantity')
            ->with(['category', 'brand'])
            ->orderBy('stock_quantity', 'asc')
            ->take($limit)
            ->get();
    }
}
