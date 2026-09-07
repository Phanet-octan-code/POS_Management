@extends('layouts.app')

@section('title', 'Profit Report')

@section('content')
<div class="container-fluid p-0">
    @include('reports.partials.header', [
        'title' => 'Profit Report',
        'subtitle' => 'Gross Profit (Sales Revenue - Product Cost) and Net Profit (Gross Profit - Operating Expenses) financial statement.',
        'icon' => 'bi-graph-up-arrow',
        'dateRange' => $dateRange
    ])

    <!-- Financial KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Revenue -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Sales Revenue"
                value="${{ number_format($summary['total_revenue'], 2) }}"
                icon="bi-cash-stack"
                color="primary"
                subtext="Refunds: -${{ number_format($summary['total_refunds'], 2) }}"
            />
        </div>
        <!-- COGS -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Product Cost (COGS)"
                value="${{ number_format($summary['total_cogs'], 2) }}"
                icon="bi-box-arrow-right"
                color="secondary"
                subtext="Direct unit inventory cost"
            />
        </div>
        <!-- Gross Profit -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Gross Profit"
                value="${{ number_format($summary['gross_profit'], 2) }}"
                icon="bi-graph-up"
                color="primary"
                subtext="Margin: {{ number_format($summary['gross_margin'], 1) }}% (Rev - COGS)"
            />
        </div>
        <!-- Net Profit -->
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Net Profit"
                value="${{ number_format($summary['net_profit'], 2) }}"
                icon="bi-piggy-bank"
                color="{{ $summary['net_profit'] >= 0 ? 'success' : 'danger' }}"
                subtext="Gross - ${{ number_format($summary['total_expenses'], 2) }} Expenses"
                badge="{{ $summary['net_profit'] >= 0 ? '+' . number_format($summary['net_margin'], 1) . '% Margin' : '-Deficit' }}"
            />
        </div>
    </div>

    <!-- Data Table: Itemized Transaction Profitability -->
    <x-card>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-0">Itemized Transaction Profitability</h5>
                <small class="text-muted">Calculates exact Gross Profit and margin contribution for each sale.</small>
            </div>
            <div class="badge bg-light text-dark border px-3 py-1.5">
                Formula: <span class="text-primary fw-bold">Gross Profit</span> = Revenue - COGS
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th>Invoice #</th>
                        <th>Sale Date</th>
                        <th>Customer</th>
                        <th class="text-center">Units Sold</th>
                        <th class="text-end">Sales Revenue</th>
                        <th class="text-end">Product Cost (COGS)</th>
                        <th class="text-end">Gross Profit</th>
                        <th class="text-center">Gross Margin</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $sale)
                        <tr>
                            <td>
                                <a href="{{ route('sales.show', $sale) }}" class="fw-bold text-primary text-decoration-none">
                                    {{ $sale->invoice_no }}
                                </a>
                            </td>
                            <td>{{ $sale->sale_date->format('M d, Y H:i') }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $sale->customer?->name ?? 'Walk-in Customer' }}</div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $sale->items_count_total }} units</span>
                            </td>
                            <td class="text-end fw-bold text-dark">
                                ${{ number_format($sale->total_amount, 2) }}
                            </td>
                            <td class="text-end text-muted">
                                ${{ number_format($sale->calculated_cogs, 2) }}
                            </td>
                            <td class="text-end fw-bold {{ $sale->calculated_gross_profit >= 0 ? 'text-success' : 'text-danger' }} fs-6">
                                ${{ number_format($sale->calculated_gross_profit, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $sale->calculated_margin >= 30 ? 'success' : ($sale->calculated_margin >= 10 ? 'primary' : 'warning') }}-subtle text-dark border">
                                    {{ number_format($sale->calculated_margin, 1) }}%
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-primary rounded-pill px-2.5" title="View Sale Breakdown">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-graph-down fs-1 d-block mb-2 text-muted"></i>
                                <div class="fw-semibold">No sales transactions in this period to calculate profit.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-3 border-top">
                {{ $records->links() }}
            </div>
        @endif
    </x-card>
</div>
@endsection
