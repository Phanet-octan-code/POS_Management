@extends('layouts.app')

@section('title', 'Supplier: ' . $supplier->name)

@section('content')
<div class="container-fluid p-0">
    <!-- Header with Action Buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Suppliers
                </a>
                <span class="text-muted">/</span>
                <span class="text-muted small fw-semibold">{{ $supplier->company_name ?? 'Vendor' }}</span>
            </div>
            <h3 class="fw-bold text-dark mb-0">{{ $supplier->name }}</h3>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3">
                <i class="bi bi-printer me-1"></i> Print Statement
            </button>
            <a href="{{ route('suppliers.index') }}" class="btn btn-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Financial Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white border-start border-primary border-4">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Procurement Value</small>
                <h3 class="fw-bold text-primary mt-2 mb-0">${{ number_format($totalPurchasesAmount, 2) }}</h3>
                <small class="text-muted">{{ $totalPurchasesCount }} purchase orders</small>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Payable Balance (Owed)</small>
                <h3 class="fw-bold {{ ($supplier->balance ?? 0) > 0 ? 'text-danger' : 'text-success' }} mt-2 mb-0">
                    ${{ number_format($supplier->balance ?? 0, 2) }}
                </h3>
                <small class="text-muted">Outstanding vendor balance</small>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Supplied Products</small>
                <h3 class="fw-bold text-dark mt-2 mb-0">{{ $supplier->products->count() }}</h3>
                <small class="text-muted">Active items sourced from this vendor</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left: Vendor Profile Information -->
        <div class="col-lg-4">
            <x-card class="mb-4">
                <div class="text-center mb-3">
                    <div class="avatar-circle rounded-circle bg-primary-subtle text-primary fw-bold mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 72px; height: 72px; font-size: 1.8rem;">
                        <i class="bi bi-building"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ $supplier->company_name ?? $supplier->name }}</h5>
                    <p class="text-muted small mb-1">Contact: {{ $supplier->name }}</p>
                    <span class="badge {{ $supplier->is_active ? 'bg-success text-white' : 'bg-secondary text-white' }} px-3 py-1 rounded-pill">
                        {{ $supplier->is_active ? 'Active Vendor' : 'Inactive Vendor' }}
                    </span>
                </div>

                <hr class="my-3 text-muted">

                <h6 class="fw-bold text-dark text-uppercase small mb-3">Contact & Identification</h6>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Phone:</span>
                        <span class="fw-bold text-dark">{{ $supplier->phone ?? 'Not provided' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Email:</span>
                        <span class="fw-bold text-dark">{{ $supplier->email ?? 'Not provided' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Tax ID / VAT:</span>
                        <code class="fw-bold text-dark">{{ $supplier->tax_number ?? 'N/A' }}</code>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Vendor Since:</span>
                        <span class="text-secondary">{{ $supplier->created_at->format('M d, Y') }}</span>
                    </li>
                </ul>

                @if($supplier->address)
                    <div class="mt-3 pt-2 border-top">
                        <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 0.72rem;">Warehouse / Office Address</small>
                        <p class="small text-dark mb-0 mt-1">{{ $supplier->address }}</p>
                    </div>
                @endif
            </x-card>

            <!-- Supplied Products Catalog Summary -->
            @if($supplier->products->isNotEmpty())
                <x-card>
                    <h6 class="fw-bold text-dark text-uppercase small mb-3">Sourced Products ({{ $supplier->products->count() }})</h6>
                    <div class="list-group list-group-flush small">
                        @foreach($supplier->products->take(8) as $prod)
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold text-dark">{{ $prod->name }}</div>
                                    <small class="text-muted font-monospace">{{ $prod->sku }}</small>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary">${{ number_format($prod->cost_price, 2) }}</div>
                                    <small class="text-muted">{{ $prod->current_stock }} on hand</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endif
        </div>

        <!-- Right: Purchase Orders History -->
        <div class="col-lg-8">
            <x-card>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-clock-history me-2 text-primary"></i> Procurement / Purchase History
                    </h5>
                    <span class="badge bg-light text-dark border">{{ $totalPurchasesCount }} Invoices</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase fw-bold">
                            <tr>
                                <th>Reference #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Order Status</th>
                                <th>Payment</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($supplier->purchases as $p)
                                <tr>
                                    <td>
                                        <a href="{{ route('purchases.show', $p) }}" class="fw-bold text-primary text-decoration-none">
                                            {{ $p->reference_no }}
                                        </a>
                                    </td>
                                    <td class="small text-muted">{{ $p->purchase_date->format('M d, Y') }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $p->items->count() }} items</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $p->status === 'received' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }} text-capitalize">
                                            {{ $p->status }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($p->payment_status === 'paid')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>
                                        @elseif($p->payment_status === 'partial')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Partial</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Unpaid</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-dark">${{ number_format($p->total_amount, 2) }}</td>
                                    <td class="text-end">
                                        @if($p->due_amount > 0)
                                            <span class="fw-bold text-danger">${{ number_format($p->due_amount, 2) }}</span>
                                        @else
                                            <span class="text-muted small">$0.00</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-box-arrow-in-down fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        No purchase orders recorded with this supplier yet.
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
