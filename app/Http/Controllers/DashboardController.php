<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Display the main POS management dashboard connected to real MySQL database.
     */
    public function index(Request $request): View
    {
        $filter = strtolower($request->get('filter', '7days'));
        if (!in_array($filter, ['today', '7days', '30days', 'this_year'])) {
            $filter = '7days';
        }

        // 1. All 12 Live Summary Cards
        $summary = $this->dashboardService->getSummaryCards();

        // 2. Chart Data
        $salesTrend = $this->dashboardService->getSalesTrendChart($filter);
        $monthlySales = $this->dashboardService->getMonthlySalesChart();
        $monthlyProfit = $this->dashboardService->getMonthlyProfitChart();

        // 3. All 3 Live Tables
        $recentSales = $this->dashboardService->getRecentSales(8);
        $topSellingProducts = $this->dashboardService->getTopSellingProducts(8);
        $lowStockProducts = $this->dashboardService->getLowStockProducts(8);

        return view('dashboard.index', compact(
            'summary',
            'filter',
            'salesTrend',
            'monthlySales',
            'monthlyProfit',
            'recentSales',
            'topSellingProducts',
            'lowStockProducts'
        ));
    }

    /**
     * AJAX endpoint to fetch updated chart trend data when switching time filters.
     */
    public function chartData(Request $request): JsonResponse
    {
        $filter = strtolower($request->get('filter', '7days'));
        if (!in_array($filter, ['today', '7days', '30days', 'this_year'])) {
            $filter = '7days';
        }

        $trend = $this->dashboardService->getSalesTrendChart($filter);

        return response()->json([
            'success' => true,
            'trend' => $trend,
        ]);
    }
}
