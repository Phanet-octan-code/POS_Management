@extends('layouts.app')

@section('title', 'Purchases & Procurement')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-truck me-2 text-primary"></i> Purchases & Procurement
            </h3>
            <p class="text-muted mb-0">Manage supplier purchase orders, incoming stock receipts, and vendor payables.</p>
        </div>
        <a href="{{ route('purchases.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> New Purchase Order
        </a>
    </div>

    <!-- Financial KPI Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-4">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Orders</small>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($totalPurchasesCount) }}</h4>
                        <small class="text-muted">Procurement transactions</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info-subtle text-info p-3 fs-4">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Procurement</small>
                        <h4 class="fw-bold text-info mb-0">${{ number_format($totalPurchasesAmount, 2) }}</h4>
                        <small class="text-muted">Gross cost of goods</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success-subtle text-success p-3 fs-4">
                        <i class="bi bi-check2-circle"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Paid to Suppliers</small>
                        <h4 class="fw-bold text-success mb-0">${{ number_format($totalPaidAmount, 2) }}</h4>
                        <small class="text-muted">Settled vendor payments</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100 {{ $totalDueAmount > 0 ? 'border-start border-danger border-4' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-danger-subtle text-danger p-3 fs-4">
                        <i class="bi bi-exclamation-octagon"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Outstanding Due</small>
                        <h4 class="fw-bold text-danger mb-0">${{ number_format($totalDueAmount, 2) }}</h4>
                        <small class="text-muted">Unpaid supplier balance</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <x-card class="mb-4">
        <form method="GET" action="{{ route('purchases.index') }}" class="row g-2 align-items-center">
            <!-- Search Keyword -->
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Reference #, notes, supplier..." value="{{ request('search') }}">
                </div>
            </div>

            <!-- Supplier Filter -->
            <div class="col-md-2">
                <select name="supplier_id" class="form-select">
                    <option value="">All Suppliers</option>
                    @foreach ($suppliers as $sup)
                        <option value="{{ $sup->id }}" {{ request('supplier_id') == $sup->id ? 'selected' : '' }}>
                            {{ $sup->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Goods Status Filter -->
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Order Statuses</option>
                    <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Received</option>
                    <option value="ordered" {{ request('status') === 'ordered' ? 'selected' : '' }}>Ordered</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>

            <!-- Payment Status Filter -->
            <div class="col-md-2">
                <select name="payment_status" class="form-select">
                    <option value="">All Payment Statuses</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>

            <!-- Date Range -->
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}" title="Date From">
            </div>

            <!-- Actions -->
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-dark w-100" title="Apply Filters"><i class="bi bi-funnel"></i></button>
                @if(request()->hasAny(['search', 'supplier_id', 'status', 'payment_status', 'date_from', 'date_to']))
                    <a href="{{ route('purchases.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Purchases Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th>Reference #</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th class="text-center">Items</th>
                        <th class="text-end">Total Amount</th>
                        <th class="text-end">Paid Amount</th>
                        <th class="text-end">Balance Due</th>
                        <th class="text-center">Goods Status</th>
                        <th class="text-center">Payment</th>
                        <th class="text-end" style="min-width: 130px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchases as $p)
                        <tr>
                            <!-- Reference Number -->
                            <td>
                                <a href="{{ route('purchases.show', $p) }}" class="fw-bold text-primary text-decoration-none font-monospace">
                                    {{ $p->reference_no }}
                                </a>
                            </td>

                            <!-- Date -->
                            <td class="text-muted small">
                                {{ $p->purchase_date->format('M d, Y') }}
                            </td>

                            <!-- Supplier -->
                            <td>
                                <div class="fw-bold text-dark">{{ $p->supplier?->name ?? 'Unknown Supplier' }}</div>
                                @if($p->supplier?->company_name)
                                    <small class="text-muted">{{ $p->supplier->company_name }}</small>
                                @endif
                            </td>

                            <!-- Items count -->
                            <td class="text-center">
                                <span class="badge bg-light text-dark border">
                                    {{ $p->items->count() }} items
                                </span>
                            </td>

                            <!-- Total Amount -->
                            <td class="text-end fw-bold text-dark">
                                ${{ number_format($p->total_amount, 2) }}
                            </td>

                            <!-- Paid Amount -->
                            <td class="text-end fw-semibold text-success">
                                ${{ number_format($p->paid_amount, 2) }}
                            </td>

                            <!-- Due Amount -->
                            <td class="text-end fw-bold {{ $p->due_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                ${{ number_format($p->due_amount, 2) }}
                            </td>

                            <!-- Goods Status -->
                            <td class="text-center">
                                <span class="badge bg-{{ $p->status_badge }}-subtle text-{{ $p->status_badge }} border border-{{ $p->status_badge }}-subtle px-2 py-1 rounded-pill text-capitalize">
                                    {{ $p->status }}
                                </span>
                            </td>

                            <!-- Payment Status -->
                            <td class="text-center">
                                <span class="badge bg-{{ $p->payment_status_badge }}-subtle text-{{ $p->payment_status_badge }} border border-{{ $p->payment_status_badge }}-subtle px-2 py-1 rounded-pill text-capitalize">
                                    {{ $p->payment_status }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('purchases.show', $p) }}" class="btn btn-outline-secondary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('purchases.invoice', $p) }}" class="btn btn-outline-dark" title="Print Invoice / Voucher" target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <a href="{{ route('purchases.edit', $p) }}" class="btn btn-outline-primary" title="Edit Purchase">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" title="Delete Purchase" onclick="confirmDeletePurchase({{ $p->id }}, '{{ $p->reference_no }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <form id="delete-form-{{ $p->id }}" action="{{ route('purchases.destroy', $p) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-truck fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <h5>No Purchase Orders Found</h5>
                                <p class="small text-muted mb-0">Record your first inventory purchase from a vendor above.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">Showing {{ $purchases->firstItem() }} to {{ $purchases->lastItem() }} of {{ $purchases->total() }} purchase orders</small>
                {{ $purchases->links() }}
            </div>
        @endif
    </x-card>
</div>

@push('scripts')
<script>
    function confirmDeletePurchase(id, ref) {
        Swal.fire({
            title: 'Delete Purchase ' + ref + '?',
            text: 'If this purchase was marked as Received, product stock levels will automatically be reversed and deducted!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete & revert stock',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-form-' + id).submit();
            }
        });
    }
</script>
@endpush
@endsection
