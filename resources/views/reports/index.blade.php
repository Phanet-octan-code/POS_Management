@extends('layouts.app')

@section('title', 'Reports & Analytics')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Reports & Analytics</h3>
            <p class="text-muted mb-0">Financial statements, sales metrics, and top-performing products.</p>
        </div>
        <!-- Date Filter Form -->
        <form method="GET" action="{{ route('reports.index') }}" class="d-flex align-items-center gap-2">
            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            <span class="text-muted">to</span>
            <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-filter"></i> Apply</button>
        </form>
    </div>

    <!-- Financial KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <x-stat-box
                title="Gross Revenue"
                value="${{ number_format($metrics['total_sales'], 2) }}"
                icon="bi-cash"
                color="primary"
                subtext="{{ $metrics['total_orders'] }} orders"
            />
        </div>
        <div class="col-md-3">
            <x-stat-box
                title="Cost of Goods (COGS)"
                value="${{ number_format($metrics['cogs'], 2) }}"
                icon="bi-box-arrow-right"
                color="secondary"
                subtext="Direct inventory costs"
            />
        </div>
        <div class="col-md-3">
            <x-stat-box
                title="Total Operating Expenses"
                value="${{ number_format($metrics['total_expenses'], 2) }}"
                icon="bi-wallet2"
                color="danger"
                subtext="Recorded store overhead"
            />
        </div>
        <div class="col-md-3">
            <x-stat-box
                title="Net Profit"
                value="${{ number_format($metrics['net_profit'], 2) }}"
                icon="bi-graph-up-arrow"
                color="success"
                subtext="Revenue - COGS - Expenses"
            />
        </div>
    </div>

    <!-- Top Selling Products Table -->
    <div class="row">
        <div class="col-12">
            <x-card title="Top Selling Products" subtitle="Ranked by total quantity sold in this period">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>SKU</th>
                                <th class="text-center">Units Sold</th>
                                <th class="text-end">Revenue Generated</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($topSellingProducts as $index => $item)
                                <tr>
                                    <td><span class="badge bg-light text-dark border">{{ $index + 1 }}</span></td>
                                    <td class="fw-bold">{{ $item->product?->name }}</td>
                                    <td><code>{{ $item->product?->sku }}</code></td>
                                    <td class="text-center fw-bold">{{ $item->total_qty }} {{ $item->product?->unit }}</td>
                                    <td class="text-end fw-bold text-success">${{ number_format($item->total_revenue, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No sales recorded for this date range.</td>
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
