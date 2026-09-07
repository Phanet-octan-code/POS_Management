@extends('layouts.app')

@section('title', 'Product Returns')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Product Returns & Refunds</h3>
            <p class="text-muted mb-0">Manage customer returns, restock returned inventory, and audit refunds.</p>
        </div>
        <a href="{{ route('returns.create') }}" class="btn btn-warning text-dark fw-bold shadow-xs">
            <i class="bi bi-arrow-return-left me-1"></i> New Product Return
        </a>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <x-card class="border-0 shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-warning-subtle text-warning">
                        <i class="bi bi-arrow-return-left fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Total Returns</span>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($totalReturnsCount) }}</h4>
                    </div>
                </div>
            </x-card>
        </div>
        <div class="col-6 col-md-4">
            <x-card class="border-0 shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-danger-subtle text-danger">
                        <i class="bi bi-cash-stack fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Total Refunded</span>
                        <h4 class="fw-bold text-danger mb-0">${{ number_format($totalRefundsSum, 2) }}</h4>
                    </div>
                </div>
            </x-card>
        </div>
        <div class="col-12 col-md-4">
            <x-card class="border-0 shadow-sm p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-primary-subtle text-primary">
                        <i class="bi bi-shield-check fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Restock Policy</span>
                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">Auto Inventory Restock</div>
                        <small class="text-muted">Stock movements logged on completion</small>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <x-card class="mb-4 shadow-sm border-0">
        <form method="GET" action="{{ route('returns.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Return #, original invoice #, customer..."
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                    <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
                <a href="{{ route('returns.index') }}" class="btn btn-sm btn-outline-secondary w-100">
                    Reset
                </a>
            </div>
        </form>
    </x-card>

    <!-- Returns Table -->
    <x-card class="shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Return No</th>
                        <th>Date & Time</th>
                        <th>Original Invoice</th>
                        <th>Customer</th>
                        <th>Staff</th>
                        <th>Refund Total</th>
                        <th>Items Count</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($returns as $ret)
                        <tr>
                            <td>
                                <a href="{{ route('returns.show', $ret) }}" class="fw-bold font-monospace text-decoration-none">
                                    {{ $ret->return_number }}
                                </a>
                            </td>
                            <td class="small text-muted">{{ $ret->return_date->format('M d, Y H:i') }}</td>
                            <td>
                                @if ($ret->sale)
                                    <a href="{{ route('sales.show', $ret->sale) }}" class="badge bg-light text-primary border text-decoration-none font-monospace">
                                        {{ $ret->sale->invoice_no }}
                                    </a>
                                @else
                                    <span class="text-muted small">N/A</span>
                                @endif
                            </td>
                            <td>{{ $ret->customer?->name ?? 'Walk-in Customer' }}</td>
                            <td class="small">{{ $ret->user?->name ?? 'Staff' }}</td>
                            <td class="fw-bold text-danger">${{ number_format($ret->total_refund, 2) }}</td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                                    {{ $ret->items->sum('quantity') }} units
                                </span>
                            </td>
                            <td class="small text-truncate" style="max-width: 160px;" title="{{ $ret->reason }}">
                                {{ $ret->reason ?? '-' }}
                            </td>
                            <td>
                                <span class="badge bg-success text-uppercase">{{ $ret->status }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('returns.show', $ret) }}" class="btn btn-sm btn-outline-primary" title="View Return Slip">
                                    <i class="bi bi-receipt me-1"></i> View Slip
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-arrow-return-left fs-1 d-block mb-2 opacity-50"></i>
                                <h6>No product returns found</h6>
                                <p class="small text-muted mb-0">Processed returns and refunds will appear here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $returns->links() }}
        </div>
    </x-card>
</div>
@endsection
