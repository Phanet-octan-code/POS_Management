@extends('layouts.app')

@section('title', 'Sales Orders')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Sales Management</h3>
            <p class="text-muted mb-0">Track and filter POS sales, print invoices, and process product returns.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('returns.create') }}" class="btn btn-outline-warning text-dark fw-semibold">
                <i class="bi bi-arrow-return-left me-1"></i> Process Return
            </a>
            <a href="{{ route('pos.index') }}" class="btn btn-primary fw-semibold">
                <i class="bi bi-cart-plus me-1"></i> Open POS Register
            </a>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <x-card class="border-0 shadow-sm h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-primary-subtle text-primary">
                        <i class="bi bi-receipt fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Total Invoices</span>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($totalSalesCount) }}</h4>
                    </div>
                </div>
            </x-card>
        </div>
        <div class="col-6 col-md-3">
            <x-card class="border-0 shadow-sm h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-success-subtle text-success">
                        <i class="bi bi-currency-dollar fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Total Revenue</span>
                        <h4 class="fw-bold text-success mb-0">${{ number_format($totalRevenue, 2) }}</h4>
                    </div>
                </div>
            </x-card>
        </div>
        <div class="col-6 col-md-3">
            <x-card class="border-0 shadow-sm h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-info-subtle text-info">
                        <i class="bi bi-wallet2 fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Total Collected</span>
                        <h4 class="fw-bold text-dark mb-0">${{ number_format($totalPaid, 2) }}</h4>
                    </div>
                </div>
            </x-card>
        </div>
        <div class="col-6 col-md-3">
            <x-card class="border-0 shadow-sm h-100 p-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 p-3 bg-danger-subtle text-danger">
                        <i class="bi bi-exclamation-circle fs-4"></i>
                    </div>
                    <div>
                        <span class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Outstanding Due</span>
                        <h4 class="fw-bold text-danger mb-0">${{ number_format($totalDue, 2) }}</h4>
                    </div>
                </div>
            </x-card>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <x-card class="mb-4 shadow-sm border-0">
        <form method="GET" action="{{ route('sales.index') }}" class="row g-2 align-items-end">
            <!-- Search Keyword -->
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Search</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                    <input type="text"
                           name="search"
                           class="form-control form-control-sm"
                           placeholder="Invoice #, customer, phone..."
                           value="{{ request('search') }}">
                </div>
            </div>

            <!-- Start Date Filter -->
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Start Date</label>
                <input type="date"
                       name="start_date"
                       class="form-control form-control-sm"
                       value="{{ request('start_date') }}">
            </div>

            <!-- End Date Filter -->
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">End Date</label>
                <input type="date"
                       name="end_date"
                       class="form-control form-control-sm"
                       value="{{ request('end_date') }}">
            </div>

            <!-- Status Filter -->
            <div class="col-6 col-md-2">
                <label class="form-label small fw-semibold text-muted mb-1">Payment Status</label>
                <select name="payment_status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>

            <!-- Method Filter -->
            <div class="col-6 col-md-1">
                <label class="form-label small fw-semibold text-muted mb-1">Method</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="aba" {{ request('payment_method') === 'aba' ? 'selected' : '' }}>ABA</option>
                    <option value="acleda" {{ request('payment_method') === 'acleda' ? 'selected' : '' }}>ACLEDA</option>
                    <option value="credit_card" {{ request('payment_method') === 'credit_card' ? 'selected' : '' }}>Card</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100 fw-semibold">
                    <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
                <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-secondary w-100" title="Reset Filters">
                    Reset
                </a>
            </div>
        </form>
    </x-card>

    <!-- Sales Invoices Table -->
    <x-card class="shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice No</th>
                        <th>Date & Time</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Due</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <!-- Invoice No -->
                            <td>
                                <a href="{{ route('sales.show', $sale) }}" class="fw-bold font-monospace text-decoration-none">
                                    {{ $sale->invoice_no }}
                                </a>
                                @if ($sale->reprint_count > 0)
                                    <span class="badge bg-secondary-subtle text-secondary border small" style="font-size: 0.65rem;" title="Reprinted {{ $sale->reprint_count }} times">
                                        Reprint #{{ $sale->reprint_count }}
                                    </span>
                                @endif
                            </td>

                            <!-- Date & Time -->
                            <td class="small text-muted">{{ $sale->sale_date->format('M d, Y H:i') }}</td>

                            <!-- Customer -->
                            <td>
                                @if ($sale->customer)
                                    <a href="{{ route('customers.show', $sale->customer) }}" class="fw-semibold text-dark text-decoration-none">
                                        {{ $sale->customer->name }}
                                    </a>
                                @else
                                    <span class="text-muted">Walk-in</span>
                                @endif
                            </td>

                            <!-- Cashier -->
                            <td class="small">{{ $sale->user?->name ?? 'Staff' }}</td>

                            <!-- Financials -->
                            <td class="fw-bold text-dark">${{ number_format($sale->total_amount, 2) }}</td>
                            <td class="text-success small">${{ number_format($sale->paid_amount, 2) }}</td>
                            <td class="small {{ $sale->due_amount > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                                ${{ number_format($sale->due_amount, 2) }}
                            </td>

                            <!-- Payment Method -->
                            <td>
                                <span class="badge bg-light text-dark border">{{ $sale->payment_method_label }}</span>
                            </td>

                            <!-- Status -->
                            <td>
                                <x-badge :type="$sale->payment_status === 'paid' ? 'success' : ($sale->payment_status === 'partial' ? 'warning' : 'danger')">
                                    {{ $sale->payment_status_label }}
                                </x-badge>
                            </td>

                            <!-- Actions -->
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-1">
                                    <!-- Print / PDF Dropdown -->
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => '80mm']) }}" target="_blank" class="btn btn-outline-primary" title="Print 80mm Receipt">
                                            <i class="bi bi-printer me-1"></i> Receipt
                                        </a>
                                        <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                            <span class="visually-hidden">Toggle Dropdown</span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                                            <li><h6 class="dropdown-header">Print Receipt</h6></li>
                                            <li><a class="dropdown-item" href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => '80mm']) }}" target="_blank"><i class="bi bi-printer me-2 text-primary"></i> 80mm Thermal</a></li>
                                            <li><a class="dropdown-item" href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => '58mm']) }}" target="_blank"><i class="bi bi-printer me-2 text-primary"></i> 58mm Thermal</a></li>
                                            <li><a class="dropdown-item" href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => 'a4']) }}" target="_blank"><i class="bi bi-file-earmark-text me-2 text-info"></i> A4 Invoice</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><h6 class="dropdown-header">Download PDF</h6></li>
                                            <li><a class="dropdown-item text-danger" href="{{ route('pos.receipt.pdf', ['sale' => $sale, 'format' => 'a4']) }}"><i class="bi bi-file-earmark-pdf me-2"></i> A4 PDF</a></li>
                                            <li><a class="dropdown-item text-danger" href="{{ route('pos.receipt.pdf', ['sale' => $sale, 'format' => '80mm']) }}"><i class="bi bi-file-earmark-pdf me-2"></i> 80mm PDF</a></li>
                                            <li><a class="dropdown-item text-danger" href="{{ route('pos.receipt.pdf', ['sale' => $sale, 'format' => '58mm']) }}"><i class="bi bi-file-earmark-pdf me-2"></i> 58mm PDF</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form action="{{ route('pos.receipt.reprint', $sale) }}" method="POST" target="_blank">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-warning" title="Record duplicate receipt reprint">
                                                        <i class="bi bi-arrow-repeat me-2"></i> Reprint Receipt
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Process Return -->
                                    <a href="{{ route('returns.create', ['invoice_no' => $sale->invoice_no]) }}" class="btn btn-sm btn-outline-warning text-dark" title="Return Products">
                                        <i class="bi bi-arrow-return-left"></i>
                                    </a>

                                    <!-- View Sale Detail -->
                                    <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-secondary" title="View Sale">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                <h6>No sales transactions found</h6>
                                <p class="small text-muted mb-0">Try changing your search terms or date filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $sales->links() }}
        </div>
    </x-card>
</div>
@endsection
