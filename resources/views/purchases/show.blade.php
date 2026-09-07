@extends('layouts.app')

@section('title', 'Purchase Order: ' . $purchase->reference_no)

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('purchases.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Purchases
                </a>
                <span class="text-muted">/</span>
                <span class="text-muted small fw-semibold font-monospace">{{ $purchase->reference_no }}</span>
            </div>
            <h3 class="fw-bold text-dark mb-0">
                Purchase Order <span class="text-primary">{{ $purchase->reference_no }}</span>
            </h3>
            <p class="text-muted mb-0">Recorded on {{ $purchase->purchase_date->format('F d, Y') }} by {{ $purchase->user?->name ?? 'Staff' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('purchases.invoice', $purchase) }}" class="btn btn-outline-dark rounded-pill px-3 shadow-sm" target="_blank">
                <i class="bi bi-printer me-1"></i> Print Invoice
            </a>
            <a href="{{ route('purchases.edit', $purchase) }}" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-pencil me-1"></i> Edit Order
            </a>
            <button type="button" class="btn btn-outline-danger rounded-pill px-3 shadow-sm" onclick="confirmDeletePurchase()">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
            <form id="delete-purchase-form" action="{{ route('purchases.destroy', $purchase) }}" method="POST" class="d-none">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    <!-- Status & Financial Summary Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Procurement Value</small>
                <h3 class="fw-bold text-primary mt-2 mb-0">${{ number_format($purchase->total_amount, 2) }}</h3>
                <small class="text-muted">{{ $purchase->items->count() }} unique product items</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Amount Paid</small>
                <h3 class="fw-bold text-success mt-2 mb-0">${{ number_format($purchase->paid_amount, 2) }}</h3>
                <small class="text-muted">Method: <span class="text-capitalize fw-semibold">{{ str_replace('_', ' ', $purchase->payment_method ?? 'Cash') }}</span></small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Outstanding Balance Due</small>
                <h3 class="fw-bold {{ $purchase->due_amount > 0 ? 'text-danger' : 'text-muted' }} mt-2 mb-0">
                    ${{ number_format($purchase->due_amount, 2) }}
                </h3>
                <small class="text-muted">{{ $purchase->due_amount > 0 ? 'Payable to supplier' : 'Fully settled' }}</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Order Status & Payment</small>
                <div class="mt-2 d-flex flex-wrap gap-1">
                    <span class="badge bg-{{ $purchase->status_badge }}-subtle text-{{ $purchase->status_badge }} border border-{{ $purchase->status_badge }}-subtle px-2 py-1 rounded-pill text-capitalize">
                        {{ $purchase->status }}
                    </span>
                    <span class="badge bg-{{ $purchase->payment_status_badge }}-subtle text-{{ $purchase->payment_status_badge }} border border-{{ $purchase->payment_status_badge }}-subtle px-2 py-1 rounded-pill text-capitalize">
                        {{ $purchase->payment_status }}
                    </span>
                </div>
                <small class="text-muted d-block mt-2">
                    @if($purchase->status === 'received')
                        <i class="bi bi-check-circle-fill text-success me-1"></i> Stock updated
                    @else
                        <i class="bi bi-clock-history text-warning me-1"></i> Pending arrival
                    @endif
                </small>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="row g-4">
        <!-- Left: Supplier & Order Info -->
        <div class="col-lg-4">
            <x-card class="mb-4">
                <h6 class="fw-bold text-dark text-uppercase small mb-3">
                    <i class="bi bi-building me-1 text-primary"></i> Vendor / Supplier
                </h6>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="rounded-circle bg-primary-subtle text-primary p-2 fs-5">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0">{{ $purchase->supplier?->name }}</h6>
                        @if($purchase->supplier?->company_name)
                            <small class="text-muted">{{ $purchase->supplier->company_name }}</small>
                        @endif
                    </div>
                </div>

                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Phone:</span>
                        <span class="fw-semibold text-dark">{{ $purchase->supplier?->phone ?? 'N/A' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Email:</span>
                        <span class="fw-semibold text-dark">{{ $purchase->supplier?->email ?? 'N/A' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Tax ID / VAT:</span>
                        <code class="text-dark">{{ $purchase->supplier?->tax_number ?? 'N/A' }}</code>
                    </li>
                    @if($purchase->supplier?->address)
                        <li class="list-group-item px-0 py-2">
                            <span class="text-muted d-block mb-1">Address:</span>
                            <span class="text-dark">{{ $purchase->supplier->address }}</span>
                        </li>
                    @endif
                </ul>
                <div class="mt-3 pt-2 border-top">
                    <a href="{{ route('suppliers.show', $purchase->supplier_id) }}" class="btn btn-sm btn-outline-primary w-100 rounded-pill">
                        <i class="bi bi-person-lines-fill me-1"></i> View Supplier Profile
                    </a>
                </div>
            </x-card>

            <!-- Order Notes -->
            @if($purchase->notes)
                <x-card class="mb-4">
                    <h6 class="fw-bold text-dark text-uppercase small mb-2">
                        <i class="bi bi-chat-left-text me-1 text-primary"></i> Order Remarks
                    </h6>
                    <p class="small text-muted mb-0">{{ $purchase->notes }}</p>
                </x-card>
            @endif

            <!-- Stock Movement Notification Box -->
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-light">
                <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-boxes text-primary fs-4 mt-1"></i>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">Inventory Synchronization</h6>
                        <p class="small text-muted mb-2">
                            @if($purchase->status === 'received')
                                All items in this order have been added to inventory stock (`Current Stock + Purchased Quantity = New Stock`).
                            @else
                                Items will automatically increment current inventory upon updating this order's status to <strong>Received</strong>.
                            @endif
                        </p>
                        <a href="{{ route('inventory.history', ['search' => $purchase->reference_no]) }}" class="small text-primary fw-semibold text-decoration-none">
                            View stock audit logs &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Itemized Table & Financial Breakdown -->
        <div class="col-lg-8">
            <x-card class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-list-check me-2 text-primary"></i> Purchased Line Items
                    </h5>
                    <span class="badge bg-light text-dark border">{{ $purchase->items->count() }} Line Items</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-uppercase small fw-bold">
                            <tr>
                                <th>Product Item</th>
                                <th>SKU</th>
                                <th class="text-center">Quantity</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Line Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchase->items as $item)
                                <tr>
                                    <!-- Product -->
                                    <td>
                                        <div class="fw-bold text-dark">{{ $item->product?->name ?? 'Deleted Product' }}</div>
                                        @if($item->product?->category)
                                            <small class="text-muted">{{ $item->product->category->name }}</small>
                                        @endif
                                    </td>

                                    <!-- SKU -->
                                    <td>
                                        <span class="badge bg-light text-dark border font-monospace">{{ $item->product?->sku ?? '—' }}</span>
                                    </td>

                                    <!-- Quantity -->
                                    <td class="text-center fw-bold fs-6">
                                        {{ number_format($item->quantity) }} {{ $item->product?->unit ?? 'units' }}
                                    </td>

                                    <!-- Unit Cost -->
                                    <td class="text-end text-muted fw-semibold">
                                        ${{ number_format($item->unit_cost, 2) }}
                                    </td>

                                    <!-- Line Subtotal -->
                                    <td class="text-end fw-bold text-dark">
                                        ${{ number_format($item->subtotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <hr class="my-4 text-muted">

                <!-- Financial Totals Summary -->
                <div class="row justify-content-end">
                    <div class="col-md-6">
                        <ul class="list-group list-group-flush small">
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <span class="text-muted">Subtotal:</span>
                                <span class="fw-bold text-dark">${{ number_format($purchase->subtotal, 2) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <span class="text-muted">Order Tax (+):</span>
                                <span class="fw-semibold text-dark">${{ number_format($purchase->tax_amount, 2) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <span class="text-muted">Order Discount (-):</span>
                                <span class="fw-semibold text-danger">-${{ number_format($purchase->discount_amount, 2) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-top">
                                <span class="fw-bold text-dark fs-6">Total Amount:</span>
                                <span class="fw-bold text-primary fs-5">${{ number_format($purchase->total_amount, 2) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-0">
                                <span class="text-muted">Amount Paid:</span>
                                <span class="fw-bold text-success">${{ number_format($purchase->paid_amount, 2) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0 py-2 border-top">
                                <span class="fw-bold text-dark">Remaining Balance Due:</span>
                                <span class="fw-bold {{ $purchase->due_amount > 0 ? 'text-danger' : 'text-muted' }} fs-6">
                                    ${{ number_format($purchase->due_amount, 2) }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function confirmDeletePurchase() {
        Swal.fire({
            title: 'Delete Purchase {{ $purchase->reference_no }}?',
            text: 'If this purchase was marked as Received, product inventory stock will be automatically reversed!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete & revert stock',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('delete-purchase-form').submit();
            }
        });
    }
</script>
@endpush
@endsection
