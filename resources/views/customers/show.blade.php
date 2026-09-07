@extends('layouts.app')

@section('title', 'Customer: ' . $customer->name)

@section('content')
<div class="container-fluid p-0">
    <!-- Header with Action Buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Customers
                </a>
                <span class="text-muted">/</span>
                <span class="text-muted small fw-semibold">Profile #{{ $customer->id }}</span>
            </div>
            <h3 class="fw-bold text-dark mb-0">{{ $customer->name }}</h3>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3">
                <i class="bi bi-printer me-1"></i> Print Statement
            </button>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary rounded-pill px-3">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>

    <!-- Financial Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white border-start border-primary border-4">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Purchases</small>
                <h3 class="fw-bold text-primary mt-2 mb-0">${{ number_format($totalPurchasesAmount, 2) }}</h3>
                <small class="text-muted">{{ $totalOrdersCount }} completed sales</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Outstanding Balance Due</small>
                <h3 class="fw-bold {{ ($customer->balance ?? 0) > 0 ? 'text-danger' : 'text-success' }} mt-2 mb-0">
                    ${{ number_format($customer->balance ?? 0, 2) }}
                </h3>
                <small class="text-muted">Unsettled balance</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Credit Limit</small>
                <h3 class="fw-bold text-dark mt-2 mb-0">${{ number_format($customer->credit_limit ?? 0, 2) }}</h3>
                <small class="text-muted">Max credit allowed</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Loyalty Reward Points</small>
                <h3 class="fw-bold text-warning mt-2 mb-0">
                    <i class="bi bi-star-fill text-warning fs-5 me-1"></i> {{ $customer->points }}
                </h3>
                <small class="text-muted">Points balance</small>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left: Customer Profile Information -->
        <div class="col-lg-4">
            <x-card class="mb-4">
                <div class="text-center mb-3">
                    <div class="avatar-circle rounded-circle bg-primary-subtle text-primary fw-bold mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 72px; height: 72px; font-size: 1.8rem;">
                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                    </div>
                    <h5 class="fw-bold text-dark mb-1">{{ $customer->name }}</h5>
                    <div>
                        @if($customer->type === 'VIP')
                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill fw-bold">
                                <i class="bi bi-gem me-1"></i> VIP Customer
                            </span>
                        @elseif($customer->type === 'Wholesale')
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1 rounded-pill fw-bold">
                                <i class="bi bi-boxes me-1"></i> Wholesale Buyer
                            </span>
                        @else
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1 rounded-pill">
                                Regular Customer
                            </span>
                        @endif
                    </div>
                </div>

                <hr class="my-3 text-muted">

                <h6 class="fw-bold text-dark text-uppercase small mb-3">Contact Details</h6>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Phone:</span>
                        <span class="fw-bold text-dark">{{ $customer->phone ?? 'Not provided' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Email:</span>
                        <span class="fw-bold text-dark">{{ $customer->email ?? 'Not provided' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Gender:</span>
                        <span class="text-capitalize text-dark">{{ $customer->gender ?? 'Unspecified' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Status:</span>
                        <span class="badge {{ $customer->is_active ? 'bg-success text-white' : 'bg-secondary text-white' }} px-2 py-1 rounded-pill">
                            {{ $customer->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Registered:</span>
                        <span class="text-secondary">{{ $customer->created_at->format('M d, Y') }}</span>
                    </li>
                </ul>

                @if($customer->address)
                    <div class="mt-3 pt-2 border-top">
                        <small class="text-muted d-block fw-semibold text-uppercase" style="font-size: 0.72rem;">Address</small>
                        <p class="small text-dark mb-0 mt-1">{{ $customer->address }}</p>
                    </div>
                @endif
            </x-card>
        </div>

        <!-- Right: Purchase History -->
        <div class="col-lg-8">
            <x-card>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="bi bi-clock-history me-2 text-primary"></i> Purchase History
                    </h5>
                    <span class="badge bg-light text-dark border">{{ $totalOrdersCount }} Transactions</span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-uppercase fw-bold">
                            <tr>
                                <th>Invoice #</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customer->sales as $sale)
                                <tr>
                                    <td>
                                        <a href="{{ route('sales.show', $sale) }}" class="fw-bold text-primary text-decoration-none">
                                            {{ $sale->invoice_no }}
                                        </a>
                                    </td>
                                    <td class="small text-muted">{{ $sale->sale_date->format('M d, Y H:i') }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $sale->items->count() }} line items</span>
                                    </td>
                                    <td>
                                        <span class="text-capitalize small fw-semibold text-muted">{{ $sale->payment_method }}</span>
                                    </td>
                                    <td>
                                        @if($sale->payment_status === 'paid')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>
                                        @elseif($sale->payment_status === 'partial')
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Partial</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Due</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-bold text-dark">${{ number_format($sale->total_amount, 2) }}</td>
                                    <td class="text-end">
                                        @if($sale->due_amount > 0)
                                            <span class="fw-bold text-danger">${{ number_format($sale->due_amount, 2) }}</span>
                                        @else
                                            <span class="text-muted small">$0.00</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        No purchase orders recorded for this customer yet.
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
