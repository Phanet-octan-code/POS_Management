@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid p-0">
    <!-- Welcome Header & Quick Action Row -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 bg-white p-4 rounded-4 shadow-sm border border-light-subtle">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                <i class="bi bi-speedometer2 fs-3"></i>
            </div>
            <div>
                <h4 class="fw-bold text-dark mb-1">Store Performance Dashboard</h4>
                <p class="text-muted small mb-0">Real-time business intelligence, live MySQL sales data, inventory alerts, and profitability.</p>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if (auth()->user()?->hasRole(['admin', 'cashier']))
                <a href="{{ route('pos.index') }}" class="btn btn-success fw-bold px-3.5 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2 text-decoration-none">
                    <i class="bi bi-cart-plus-fill fs-5"></i>
                    <span>Open POS Terminal</span>
                </a>
            @endif
            @if (auth()->user()?->hasRole(['admin', 'manager', 'staff']))
                <a href="{{ route('sales.index') }}" class="btn btn-outline-primary fw-semibold px-3 py-2 rounded-3 d-flex align-items-center gap-1.5 text-decoration-none">
                    <i class="bi bi-receipt"></i>
                    <span>All Sales</span>
                </a>
            @endif
            @if (auth()->user()?->hasRole(['admin', 'manager']))
                <a href="{{ route('expenses.index') }}" class="btn btn-outline-danger fw-semibold px-3 py-2 rounded-3 d-flex align-items-center gap-1.5 text-decoration-none">
                    <i class="bi bi-wallet2"></i>
                    <span>Expenses</span>
                </a>
            @endif
        </div>
    </div>

    <!-- ==================== 12 LIVE SUMMARY KPI CARDS ==================== -->
    <!-- Section 1: Sales & Orders -->
    <h6 class="text-uppercase text-muted fw-bold mb-3 small d-flex align-items-center gap-2">
        <i class="bi bi-cash-stack text-primary"></i> Revenue & Sales Metrics
    </h6>
    <div class="row g-3 mb-4">
        <!-- 1. Total Sales -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Total Sales"
                value="${{ number_format($summary['total_sales'], 2) }}"
                icon="bi-currency-dollar"
                color="primary"
                subtext="Cumulative gross sales revenue"
                badge="All-Time"
            />
        </div>
        <!-- 2. Today's Sales -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Today's Sales"
                value="${{ number_format($summary['today_sales'], 2) }}"
                icon="bi-sun"
                color="info"
                subtext="Sales collected today"
                badge="+Live"
            />
        </div>
        <!-- 3. Monthly Sales -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Monthly Sales"
                value="${{ number_format($summary['monthly_sales'], 2) }}"
                icon="bi-calendar-month"
                color="primary"
                subtext="{{ now()->format('F Y') }} sales total"
                badge="{{ now()->format('M') }}"
            />
        </div>
        <!-- 4. Total Orders -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Total Orders"
                value="{{ number_format($summary['total_orders']) }}"
                icon="bi-receipt"
                color="secondary"
                subtext="Completed sales transactions"
            />
        </div>
    </div>

    <!-- Section 2: Profitability & Operating Expenses -->
    <h6 class="text-uppercase text-muted fw-bold mb-3 small d-flex align-items-center gap-2">
        <i class="bi bi-graph-up-arrow text-success"></i> Financial Health & Profitability
    </h6>
    <div class="row g-3 mb-4">
        <!-- 5. Gross Profit -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Gross Profit"
                value="${{ number_format($summary['gross_profit'], 2) }}"
                icon="bi-graph-up"
                color="primary"
                subtext="Net Sales minus Net COGS"
            />
        </div>
        <!-- 6. Total Expenses -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Total Expenses"
                value="${{ number_format($summary['total_expenses'], 2) }}"
                icon="bi-wallet2"
                color="danger"
                subtext="Recorded store overhead costs"
            />
        </div>
        <!-- 7. Net Profit -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Net Profit"
                value="${{ number_format($summary['net_profit'], 2) }}"
                icon="bi-piggy-bank"
                color="{{ $summary['net_profit'] >= 0 ? 'success' : 'danger' }}"
                subtext="Gross Profit minus Expenses"
                badge="{{ $summary['net_profit'] >= 0 ? '+Net Gain' : '-Net Deficit' }}"
            />
        </div>
        <!-- 8. Total Products -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Total Products"
                value="{{ number_format($summary['total_products']) }}"
                icon="bi-box-seam"
                color="dark"
                subtext="Registered catalog items"
            />
        </div>
    </div>

    <!-- Section 3: Inventory & Directory -->
    <h6 class="text-uppercase text-muted fw-bold mb-3 small d-flex align-items-center gap-2">
        <i class="bi bi-shield-check text-warning"></i> Inventory Status & Directory
    </h6>
    <div class="row g-3 mb-4">
        <!-- 9. Low Stock -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Low Stock"
                value="{{ number_format($summary['low_stock']) }}"
                icon="bi-exclamation-triangle"
                color="warning"
                subtext="At or below minimum stock"
                badge="{{ $summary['low_stock'] > 0 ? 'Action Needed' : 'Normal' }}"
            />
        </div>
        <!-- 10. Out of Stock -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Out of Stock"
                value="{{ number_format($summary['out_of_stock']) }}"
                icon="bi-x-octagon"
                color="danger"
                subtext="Items with zero quantity"
                badge="{{ $summary['out_of_stock'] > 0 ? 'Depleted' : 'Healthy' }}"
            />
        </div>
        <!-- 11. Total Customers -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Total Customers"
                value="{{ number_format($summary['total_customers']) }}"
                icon="bi-people"
                color="info"
                subtext="Active customer accounts"
            />
        </div>
        <!-- 12. Total Suppliers -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Total Suppliers"
                value="{{ number_format($summary['total_suppliers']) }}"
                icon="bi-truck"
                color="secondary"
                subtext="Procurement vendor partners"
            />
        </div>
    </div>

    <!-- ==================== CHARTS SECTION WITH FILTERS ==================== -->
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h5 class="fw-bold text-dark mb-1">Sales Trends & Visual Analytics</h5>
                    <p class="text-muted small mb-0">Dynamic performance graphs computed from transaction ledger records.</p>
                </div>
                <!-- Interactive Time Filter Buttons -->
                <div class="btn-group p-1 bg-light rounded-pill border" role="group" id="chartFilterGroup">
                    <button type="button" class="btn btn-sm rounded-pill px-3 filter-btn {{ $filter === 'today' ? 'btn-primary active text-white fw-bold shadow-sm' : 'btn-light text-dark' }}" data-filter="today">
                        Today
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 filter-btn {{ $filter === '7days' ? 'btn-primary active text-white fw-bold shadow-sm' : 'btn-light text-dark' }}" data-filter="7days">
                        Last 7 Days
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 filter-btn {{ $filter === '30days' ? 'btn-primary active text-white fw-bold shadow-sm' : 'btn-light text-dark' }}" data-filter="30days">
                        Last 30 Days
                    </button>
                    <button type="button" class="btn btn-sm rounded-pill px-3 filter-btn {{ $filter === 'this_year' ? 'btn-primary active text-white fw-bold shadow-sm' : 'btn-light text-dark' }}" data-filter="this_year">
                        This Year
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <!-- 1. Sales by Day / Filter Trend Chart -->
                <div class="col-lg-8">
                    <div class="p-3 bg-light rounded-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0" id="salesTrendTitle">{{ $salesTrend['title'] }}</h6>
                            <span class="badge bg-primary px-2 py-1 fs-7">
                                Total: $<span id="salesTrendTotal">{{ number_format($salesTrend['total'], 2) }}</span>
                            </span>
                        </div>
                        <div style="height: 320px;" class="position-relative">
                            <canvas id="salesTrendChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- 2. Sales by Month (Current Year) -->
                <div class="col-lg-4">
                    <div class="p-3 bg-light rounded-3 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">Sales by Month ({{ $monthlySales['year'] }})</h6>
                            <span class="badge bg-secondary px-2 py-1 fs-7">
                                YTD: ${{ number_format($monthlySales['total_year'], 2) }}
                            </span>
                        </div>
                        <div style="height: 320px;" class="position-relative">
                            <canvas id="monthlySalesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- 3. Profit by Month (Gross Profit vs Net Profit) -->
                <div class="col-12">
                    <div class="p-3 bg-light rounded-3">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-3">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Profit by Month (Gross Profit vs. Net Profit) - {{ $monthlyProfit['year'] }}</h6>
                                <small class="text-muted">Direct comparison showing revenue remaining after deducting Cost of Goods Sold (COGS) and Operating Expenses.</small>
                            </div>
                            <div class="d-flex gap-3">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    <i class="bi bi-square-fill me-1"></i> Gross Profit
                                </span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="bi bi-square-fill me-1"></i> Net Profit
                                </span>
                            </div>
                        </div>
                        <div style="height: 280px;" class="position-relative">
                            <canvas id="monthlyProfitChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== 3 LIVE DATA TABLES ==================== -->
    <div class="row g-4 mb-4">
        <!-- Table 1: Recent Sales -->
        <div class="col-12">
            <x-card>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">
                            <i class="bi bi-receipt text-primary me-2"></i> Recent Sales
                        </h5>
                        <p class="text-muted small mb-0">Latest transactions finalized at the POS checkout.</p>
                    </div>
                    <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        View All Sales <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small fw-bold">
                            <tr>
                                <th>Invoice</th>
                                <th>Customer</th>
                                <th>Cashier</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Payment Status</th>
                                <th class="text-center">Date</th>
                                <th class="text-end">Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSales as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.show', $sale) }}" class="fw-bold text-primary text-decoration-none">
                                            {{ $sale->invoice_no }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $sale->customer?->name ?? 'Walk-in Customer' }}</div>
                                        @if($sale->customer?->phone)
                                            <small class="text-muted">{{ $sale->customer->phone }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-person me-1"></i> {{ $sale->user?->name ?? 'Staff' }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-dark fs-6">
                                        ${{ number_format($sale->total_amount, 2) }}
                                    </td>
                                    <td class="text-center">
                                        <x-badge :type="$sale->payment_status === 'paid' ? 'success' : ($sale->payment_status === 'partial' ? 'warning' : 'danger')">
                                            {{ ucfirst($sale->payment_status) }}
                                        </x-badge>
                                    </td>
                                    <td class="text-center text-muted small">
                                        {{ $sale->sale_date->format('M d, Y H:i') }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => '80mm']) }}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-2.5" title="Print Receipt">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">No sales transactions recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <!-- Table 2: Top Selling Products -->
        <div class="col-lg-6">
            <x-card>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">
                            <i class="bi bi-trophy text-warning me-2"></i> Top Selling Products
                        </h5>
                        <p class="text-muted small mb-0">Highest volume items by total units sold.</p>
                    </div>
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        View Products <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small fw-bold">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Quantity Sold</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topSellingProducts as $item)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark">{{ $item->product?->name ?? 'Deleted Product' }}</div>
                                        <small class="text-muted">
                                            SKU: {{ $item->product?->sku ?? 'N/A' }}
                                            @if($item->product?->category)
                                                | {{ $item->product->category->name }}
                                            @endif
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fw-bold fs-7">
                                            {{ $item->total_qty }} {{ $item->product?->unit ?? 'pcs' }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold text-dark">
                                        ${{ number_format($item->total_revenue, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">No product sales recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        <!-- Table 3: Low Stock Warnings -->
        <div class="col-lg-6">
            <x-card>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold text-dark mb-1">
                            <i class="bi bi-exclamation-octagon text-danger me-2"></i> Low Stock Warnings
                        </h5>
                        <p class="text-muted small mb-0">Items requiring procurement replenishment.</p>
                    </div>
                    <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                        Manage Inventory <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small fw-bold">
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-center">Stock</th>
                                <th class="text-center">Minimum Stock</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lowStockProducts as $prod)
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-dark text-truncate" style="max-width: 180px;">{{ $prod->name }}</div>
                                    </td>
                                    <td><code>{{ $prod->sku }}</code></td>
                                    <td class="text-center fw-bold {{ $prod->stock_quantity <= 0 ? 'text-danger' : 'text-warning' }}">
                                        {{ $prod->stock_quantity }} {{ $prod->unit }}
                                    </td>
                                    <td class="text-center text-muted">
                                        {{ $prod->alert_quantity }} {{ $prod->unit }}
                                    </td>
                                    <td class="text-center">
                                        @if($prod->stock_quantity <= 0)
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                                Out of Stock
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                                Low Stock
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                                        All inventory stocks are healthy.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------
    // 1. Sales Trend Chart (Sales by Day / Hourly / Filtered)
    // -------------------------------------------------------------
    const trendCtx = document.getElementById('salesTrendChart').getContext('2d');
    const initialTrend = @json($salesTrend);

    const salesTrendChart = new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: initialTrend.labels,
            datasets: [{
                label: 'Sales ($)',
                data: initialTrend.data,
                borderColor: '#4338ca',
                backgroundColor: 'rgba(67, 56, 202, 0.08)',
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#4338ca',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (ctx) {
                            return ' Sales: $' + Number(ctx.parsed.y).toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        callback: function (val) {
                            return '$' + val;
                        }
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Handle Time Range Filter Clicks (Today, Last 7 Days, Last 30 Days, This Year)
    document.querySelectorAll('#chartFilterGroup .filter-btn').forEach(button => {
        button.addEventListener('click', async function () {
            const filter = this.dataset.filter;

            // Update button styles
            document.querySelectorAll('#chartFilterGroup .filter-btn').forEach(btn => {
                btn.className = 'btn btn-sm rounded-pill px-3 filter-btn btn-light text-dark';
            });
            this.className = 'btn btn-sm rounded-pill px-3 filter-btn btn-primary active text-white fw-bold shadow-sm';

            try {
                const response = await fetch(`/dashboard/chart-data?filter=${filter}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();

                if (result.success && result.trend) {
                    document.getElementById('salesTrendTitle').textContent = result.trend.title;
                    document.getElementById('salesTrendTotal').textContent = Number(result.trend.total).toFixed(2);

                    salesTrendChart.data.labels = result.trend.labels;
                    salesTrendChart.data.datasets[0].data = result.trend.data;
                    salesTrendChart.update();
                }
            } catch (err) {
                console.error('Error fetching chart data:', err);
            }
        });
    });

    // -------------------------------------------------------------
    // 2. Sales by Month Chart (12 Months of Current Year)
    // -------------------------------------------------------------
    const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
    const monthlySalesData = @json($monthlySales);

    new Chart(monthlySalesCtx, {
        type: 'bar',
        data: {
            labels: monthlySalesData.labels,
            datasets: [{
                label: 'Monthly Sales ($)',
                data: monthlySalesData.data,
                backgroundColor: 'rgba(79, 70, 229, 0.85)',
                hoverBackgroundColor: '#4338ca',
                borderRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 10,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (ctx) {
                            return ' Monthly Sales: $' + Number(ctx.parsed.y).toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        callback: function (val) {
                            return '$' + val;
                        }
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // -------------------------------------------------------------
    // 3. Profit by Month Chart (Gross Profit vs Net Profit)
    // -------------------------------------------------------------
    const monthlyProfitCtx = document.getElementById('monthlyProfitChart').getContext('2d');
    const monthlyProfitData = @json($monthlyProfit);

    new Chart(monthlyProfitCtx, {
        type: 'bar',
        data: {
            labels: monthlyProfitData.labels,
            datasets: [
                {
                    label: 'Gross Profit ($)',
                    data: monthlyProfitData.gross_profit,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderRadius: 6,
                    barPercentage: 0.7,
                },
                {
                    label: 'Net Profit ($)',
                    data: monthlyProfitData.net_profit,
                    backgroundColor: 'rgba(16, 185, 129, 0.85)',
                    borderRadius: 6,
                    barPercentage: 0.7,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function (ctx) {
                            return ' ' + ctx.dataset.label + ': $' + Number(ctx.parsed.y).toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        callback: function (val) {
                            return '$' + val;
                        }
                    }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
@endpush
