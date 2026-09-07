@extends('layouts.app')

@section('title', 'Sales Report')

@section('content')
<div class="container-fluid p-0">
    @include('reports.partials.header', [
        'title' => 'Sales Report',
        'subtitle' => 'Detailed statement of checkout invoices, transaction revenues, discounts, and customer payments.',
        'icon' => 'bi-receipt',
        'dateRange' => $dateRange
    ])

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-2 col-md-4 col-6">
            <x-stat-box
                title="Total Sales"
                value="${{ number_format($summary['total_sales'], 2) }}"
                icon="bi-currency-dollar"
                color="primary"
                subtext="Gross period revenue"
            />
        </div>
        <div class="col-xl-2 col-md-4 col-6">
            <x-stat-box
                title="Invoices Count"
                value="{{ number_format($summary['total_invoices']) }}"
                icon="bi-receipt-cutoff"
                color="success"
                subtext="Processed sales"
            />
        </div>
        <div class="col-xl-3 col-md-4 col-6">
            <x-stat-box
                title="Avg Order Value"
                value="${{ number_format($summary['avg_order_value'], 2) }}"
                icon="bi-cart-check"
                color="info"
                subtext="Revenue per checkout"
            />
        </div>
        <div class="col-xl-2 col-md-6 col-6">
            <x-stat-box
                title="Total Discounts"
                value="-${{ number_format($summary['total_discount'], 2) }}"
                icon="bi-tag"
                color="danger"
                subtext="Promotions granted"
            />
        </div>
        <div class="col-xl-3 col-md-6 col-12">
            <x-stat-box
                title="Tax Collected"
                value="${{ number_format($summary['total_tax'], 2) }}"
                icon="bi-bank"
                color="secondary"
                subtext="Government tax liability"
            />
        </div>
    </div>

    <!-- Data Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'invoice_no', 'sort_dir' => request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                Invoice #
                                @if(request('sort_by') === 'invoice_no')
                                    <i class="bi bi-arrow-{{ request('sort_dir') === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'sale_date', 'sort_dir' => request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                Date & Time
                                @if(request('sort_by', 'sale_date') === 'sale_date')
                                    <i class="bi bi-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </a>
                        </th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Tax</th>
                        <th class="text-end">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'total_amount', 'sort_dir' => request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center justify-content-end gap-1">
                                Total Amount
                                @if(request('sort_by') === 'total_amount')
                                    <i class="bi bi-arrow-{{ request('sort_dir') === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </a>
                        </th>
                        <th class="text-center">Payment</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Receipt</th>
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
                            <td>
                                <span class="text-dark">{{ $sale->sale_date->format('M d, Y') }}</span>
                                <small class="text-muted d-block">{{ $sale->sale_date->format('H:i') }}</small>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $sale->customer?->name ?? 'Walk-in Customer' }}</div>
                                @if($sale->customer?->phone)
                                    <small class="text-muted">{{ $sale->customer->phone }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $sale->user?->name ?? 'Staff' }}
                                </span>
                            </td>
                            <td class="text-end">${{ number_format($sale->subtotal, 2) }}</td>
                            <td class="text-end text-danger">
                                @if($sale->discount_amount > 0)
                                    -${{ number_format($sale->discount_amount, 2) }}
                                @else
                                    <span class="text-muted">$0.00</span>
                                @endif
                            </td>
                            <td class="text-end text-muted">${{ number_format($sale->tax_amount, 2) }}</td>
                            <td class="text-end fw-bold fs-6 text-primary">
                                ${{ number_format($sale->total_amount, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.72rem;">
                                    {{ $sale->payment_method_label }}
                                </span>
                            </td>
                            <td class="text-center">
                                <x-badge :type="$sale->payment_status === 'paid' ? 'success' : ($sale->payment_status === 'partial' ? 'warning' : 'danger')">
                                    {{ ucfirst($sale->payment_status) }}
                                </x-badge>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => '80mm']) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="Print Receipt">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-receipt fs-1 d-block mb-2 text-muted"></i>
                                <div class="fw-semibold">No sales transactions found for this timeframe.</div>
                                <small>Try adjusting your search criteria or select a broader date preset.</small>
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
