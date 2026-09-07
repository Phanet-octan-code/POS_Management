@extends('layouts.app')

@section('title', 'Return Slip ' . $return->return_number)

@section('content')
<div class="container-fluid p-0" style="max-width: 900px;">
    <!-- Action Bar (Hidden during print) -->
    <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
        <div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger fs-6 px-3 py-1">Product Return</span>
                <h3 class="fw-bold text-dark mb-0">{{ $return->return_number }}</h3>
            </div>
            <p class="text-muted mb-0 mt-1">Processed on {{ $return->return_date->format('M d, Y H:i:s') }}</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Print Return Slip
            </button>
            @if ($return->sale)
                <a href="{{ route('sales.show', $return->sale) }}" class="btn btn-outline-primary">
                    <i class="bi bi-receipt me-1"></i> Original Sale #{{ $return->sale->invoice_no }}
                </a>
            @endif
            <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Returns
            </a>
        </div>
    </div>

    <!-- Printable Voucher Slip Container -->
    <div class="card shadow-sm border-0 mb-4 print-container">
        <div class="card-body p-4 p-md-5">
            <!-- Header for Print Voucher -->
            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <img src="{{ asset('images/store-logo.svg') }}" alt="Store Logo" height="38">
                        <span class="fs-4 fw-bold text-primary">POS MANAGEMENT</span>
                    </div>
                    <p class="text-muted small mb-0">
                        123 Retail Boulevard, Commerce City<br>
                        Phone: +1 (555) 019-2834 | Email: support@posretail.local
                    </p>
                </div>
                <div class="text-end">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fs-6 px-3 py-1 mb-2 d-inline-block">
                        CREDIT RETURN VOUCHER
                    </span>
                    <div class="fw-bold fs-5 text-dark">{{ $return->return_number }}</div>
                    <div class="text-muted small">Date: {{ $return->return_date->format('M d, Y h:i A') }}</div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="row g-3 mb-4 p-3 bg-light rounded-3">
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.72rem;">Original Invoice</small>
                    @if ($return->sale)
                        <a href="{{ route('sales.show', $return->sale) }}" class="fw-bold text-primary text-decoration-none d-print-none">
                            {{ $return->sale->invoice_no }}
                        </a>
                        <span class="fw-bold text-dark d-none d-print-inline">{{ $return->sale->invoice_no }}</span>
                    @else
                        <span class="text-muted">N/A</span>
                    @endif
                </div>
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.72rem;">Customer</small>
                    <strong>{{ $return->customer?->name ?? $return->sale?->customer?->name ?? 'Walk-in Customer' }}</strong>
                    @if ($return->customer?->phone || $return->sale?->customer?->phone)
                        <div class="text-muted small">{{ $return->customer?->phone ?? $return->sale?->customer?->phone }}</div>
                    @endif
                </div>
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.72rem;">Processed By</small>
                    <strong>{{ $return->user?->name ?? 'Cashier / Staff' }}</strong>
                </div>
                <div class="col-sm-6 col-md-3">
                    <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.72rem;">Return Status</small>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                        <i class="bi bi-check-circle me-1"></i> Completed
                    </span>
                </div>
            </div>

            <!-- Reason Banner -->
            <div class="alert alert-secondary py-2 px-3 mb-4 d-flex align-items-center gap-2">
                <i class="bi bi-info-circle text-primary"></i>
                <div class="small">
                    <strong>Return Reason:</strong> {{ $return->reason ?? 'Customer return / refund' }}
                </div>
            </div>

            <!-- Returned Items Table -->
            <h6 class="fw-bold text-dark mb-3">Returned Product Items</h6>
            <div class="table-responsive mb-4">
                <table class="table table-hover align-middle border">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 45%;">Product Description</th>
                            <th class="text-center" style="width: 15%;">Condition</th>
                            <th class="text-center" style="width: 15%;">Qty Returned</th>
                            <th class="text-end" style="width: 10%;">Refund Rate</th>
                            <th class="text-end" style="width: 10%;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($return->items as $index => $item)
                            <tr>
                                <td class="text-muted">{{ $index + 1 }}</td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->product?->name ?? 'Deleted Product' }}</div>
                                    <small class="text-muted">
                                        SKU: {{ $item->product?->sku ?? 'N/A' }}
                                        @if ($item->product?->barcode) | Barcode: {{ $item->product->barcode }} @endif
                                    </small>
                                </td>
                                <td class="text-center">
                                    @if ($item->condition === 'resellable')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="bi bi-box-seam me-1"></i> Resellable
                                        </span>
                                    @elseif ($item->condition === 'damaged')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="bi bi-exclamation-triangle me-1"></i> Damaged
                                        </span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                            <i class="bi bi-shield-x me-1"></i> Defective
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold fs-6">{{ $item->quantity }}</td>
                                <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end fw-bold">${{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="5" class="text-end fw-bold fs-6">Total Refund Amount:</td>
                            <td class="text-end fw-bold fs-5 text-danger">${{ number_format($return->total_refund, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Inventory Notice -->
            <div class="d-flex align-items-center gap-2 p-3 bg-light rounded-3 text-muted small mb-4">
                <i class="bi bi-arrow-repeat text-success fs-5"></i>
                <div>
                    <strong>Inventory Adjustment:</strong> Product inventory counts have been automatically credited back with corresponding stock movement audit records (Type: <code>return</code>).
                </div>
            </div>

            <!-- Signatures Section (for printable voucher) -->
            <div class="row pt-5 mt-4 text-center border-top">
                <div class="col-6">
                    <div class="border-bottom mx-auto mb-2" style="width: 200px; height: 40px;"></div>
                    <small class="text-muted fw-semibold">Customer Signature</small>
                </div>
                <div class="col-6">
                    <div class="border-bottom mx-auto mb-2" style="width: 200px; height: 40px;"></div>
                    <small class="text-muted fw-semibold">Authorized Cashier / Manager</small>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    body {
        background-color: #fff !important;
        font-size: 11pt;
    }
    .navbar, .sidebar, .main-sidebar, .btn, .d-print-none, header, footer {
        display: none !important;
    }
    .container-fluid {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    .print-container {
        border: none !important;
        box-shadow: none !important;
    }
    .table-light {
        background-color: #f8f9fa !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .badge {
        border: 1px solid #ccc !important;
        color: #000 !important;
    }
}
</style>
@endpush
@endsection
