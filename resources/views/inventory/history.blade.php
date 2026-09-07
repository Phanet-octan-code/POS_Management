@extends('layouts.app')

@section('title', 'Stock Movement History')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Inventory
                </a>
                <span class="text-muted">/</span>
                <span class="text-muted small fw-semibold">Audit Logs</span>
            </div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-clock-history me-2 text-primary"></i> Stock Movement History
            </h3>
            <p class="text-muted mb-0">Complete audit trail of all inventory receipts, sales deductions, damages, and manual adjustments.</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3 shadow-sm">
                <i class="bi bi-printer me-1"></i> Print Log
            </button>
            <a href="{{ route('inventory.index') }}" class="btn btn-secondary rounded-pill px-3 shadow-sm">
                <i class="bi bi-boxes me-1"></i> Current Stock Levels
            </a>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <x-card class="mb-4">
        <form method="GET" action="{{ route('inventory.history') }}" class="row g-2 align-items-center">
            <!-- Product Filter -->
            <div class="col-md-3">
                <select name="product_id" class="form-select">
                    <option value="">All Products</option>
                    @foreach ($products as $p)
                        <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} ({{ $p->sku }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Movement Type Filter -->
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Movement Types</option>
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date Range -->
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" title="Date From">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}" title="Date To">
            </div>

            <!-- Search Button -->
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                @if(request()->hasAny(['product_id', 'type', 'date_from', 'date_to', 'search']))
                    <a href="{{ route('inventory.history') }}" class="btn btn-outline-secondary" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Movement History Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th style="min-width: 150px;">Date & Time</th>
                        <th style="min-width: 220px;">Product</th>
                        <th>Type</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-center">Previous Stock</th>
                        <th class="text-center">New Stock</th>
                        <th>User</th>
                        <th style="min-width: 200px;">Reason / Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $t)
                        <tr>
                            <!-- Date & Time -->
                            <td>
                                <div class="fw-semibold text-dark">{{ $t->created_at->format('M d, Y') }}</div>
                                <small class="text-muted font-monospace">{{ $t->created_at->format('H:i:s') }}</small>
                            </td>

                            <!-- Product -->
                            <td>
                                <div class="fw-bold text-dark">{{ $t->product?->name ?? 'Deleted Product' }}</div>
                                @if($t->product)
                                    <small class="text-muted font-monospace">SKU: {{ $t->product->sku }}</small>
                                @endif
                            </td>

                            <!-- Movement Type -->
                            <td>
                                <span class="badge bg-{{ $t->type_badge }}-subtle text-{{ $t->type_badge }} border border-{{ $t->type_badge }}-subtle px-2 py-1 rounded-pill">
                                    {{ $t->type_label }}
                                </span>
                            </td>

                            <!-- Quantity Change -->
                            <td class="text-center">
                                <span class="fw-bold fs-6 {{ $t->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $t->quantity > 0 ? '+' . number_format($t->quantity) : number_format($t->quantity) }}
                                </span>
                            </td>

                            <!-- Previous Stock -->
                            <td class="text-center">
                                <span class="badge bg-light text-dark border font-monospace fs-6">
                                    {{ number_format($t->stock_before) }}
                                </span>
                            </td>

                            <!-- New Stock -->
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-6">
                                    {{ number_format($t->stock_after) }}
                                </span>
                            </td>

                            <!-- User -->
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-person-circle text-secondary"></i>
                                    <span class="small fw-semibold text-dark">{{ $t->user?->name ?? 'System Automated' }}</span>
                                </div>
                            </td>

                            <!-- Reason / Remarks -->
                            <td>
                                <span class="text-dark small">{{ $t->reason ?? '—' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-clock-history fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <h5>No Stock Movements Recorded</h5>
                                <p class="small text-muted mb-0">Adjustments, procurement receipts, and sales deductions will automatically log here.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($transactions->hasPages())
            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} movements</small>
                {{ $transactions->links() }}
            </div>
        @endif
    </x-card>
</div>
@endsection
