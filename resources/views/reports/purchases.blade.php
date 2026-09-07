@extends('layouts.app')

@section('title', 'Purchase Report')

@section('content')
<div class="container-fluid p-0">
    @include('reports.partials.header', [
        'title' => 'Purchase Report',
        'subtitle' => 'Vendor procurement orders, inventory sourcing expenditures, paid amounts, and outstanding payables.',
        'icon' => 'bi-bag-check',
        'dateRange' => $dateRange
    ])

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Total Procurement"
                value="${{ number_format($summary['total_purchases'], 2) }}"
                icon="bi-box-seam"
                color="primary"
                subtext="Total stock purchase value"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Purchase Orders"
                value="{{ number_format($summary['total_orders']) }}"
                icon="bi-receipt"
                color="info"
                subtext="Vendor transactions"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Total Paid"
                value="${{ number_format($summary['total_paid'], 2) }}"
                icon="bi-check2-circle"
                color="success"
                subtext="Disbursed to suppliers"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Outstanding Payables"
                value="${{ number_format($summary['total_due'], 2) }}"
                icon="bi-hourglass-split"
                color="danger"
                subtext="Pending vendor balance"
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
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'purchase_number', 'sort_dir' => request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                Purchase #
                                @if(request('sort_by') === 'purchase_number')
                                    <i class="bi bi-arrow-{{ request('sort_dir') === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'purchase_date', 'sort_dir' => request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                Date
                                @if(request('sort_by', 'purchase_date') === 'purchase_date')
                                    <i class="bi bi-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </a>
                        </th>
                        <th>Supplier</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'total_amount', 'sort_dir' => request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center justify-content-end gap-1">
                                Total Cost
                                @if(request('sort_by') === 'total_amount')
                                    <i class="bi bi-arrow-{{ request('sort_dir') === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </a>
                        </th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Due</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Invoice</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $purchase)
                        <tr>
                            <td>
                                <a href="{{ route('purchases.show', $purchase) }}" class="fw-bold text-primary text-decoration-none">
                                    {{ $purchase->purchase_number }}
                                </a>
                            </td>
                            <td>{{ $purchase->purchase_date->format('M d, Y') }}</td>
                            <td>
                                <div class="fw-semibold text-dark">{{ $purchase->supplier?->name ?? 'N/A' }}</div>
                                @if($purchase->supplier?->company)
                                    <small class="text-muted">{{ $purchase->supplier->company }}</small>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">{{ $purchase->items->count() }} items</span>
                            </td>
                            <td class="text-end fw-bold text-dark fs-6">
                                ${{ number_format($purchase->total_amount, 2) }}
                            </td>
                            <td class="text-end text-success fw-semibold">
                                ${{ number_format($purchase->paid_amount, 2) }}
                            </td>
                            <td class="text-end text-danger fw-semibold">
                                ${{ number_format($purchase->due_amount, 2) }}
                            </td>
                            <td class="text-center">
                                <x-badge :type="$purchase->payment_status === 'paid' ? 'success' : ($purchase->payment_status === 'partial' ? 'warning' : 'danger')">
                                    {{ ucfirst($purchase->payment_status) }}
                                </x-badge>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('purchases.invoice', $purchase) }}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" title="Purchase Invoice">
                                    <i class="bi bi-file-earmark-text"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-bag-x fs-1 d-block mb-2 text-muted"></i>
                                <div class="fw-semibold">No purchase records found for this period.</div>
                                <small>Select another date range or clear search filters.</small>
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
