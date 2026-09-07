@extends('layouts.app')

@section('title', 'Sale Invoice ' . $sale->invoice_no)

@section('content')
<div class="container-fluid p-0" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Invoice {{ $sale->invoice_no }}</h3>
            <p class="text-muted mb-0">Processed on {{ $sale->sale_date->format('M d, Y H:i:s') }}</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="btn-group">
                <a href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => '80mm']) }}" target="_blank" class="btn btn-primary">
                    <i class="bi bi-printer me-1"></i> Print Receipt
                </a>
                <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Toggle Dropdown</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header">Print Formats</h6></li>
                    <li><a class="dropdown-item" href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => '80mm']) }}" target="_blank"><i class="bi bi-printer me-2"></i> 80mm Thermal</a></li>
                    <li><a class="dropdown-item" href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => '58mm']) }}" target="_blank"><i class="bi bi-printer me-2"></i> 58mm Thermal</a></li>
                    <li><a class="dropdown-item" href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => 'a4']) }}" target="_blank"><i class="bi bi-file-earmark-text me-2"></i> A4 Invoice</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><h6 class="dropdown-header">Download PDF (DomPDF)</h6></li>
                    <li><a class="dropdown-item text-danger" href="{{ route('pos.receipt.pdf', ['sale' => $sale, 'format' => 'a4']) }}"><i class="bi bi-file-earmark-pdf me-2"></i> A4 PDF</a></li>
                    <li><a class="dropdown-item text-danger" href="{{ route('pos.receipt.pdf', ['sale' => $sale, 'format' => '80mm']) }}"><i class="bi bi-file-earmark-pdf me-2"></i> 80mm PDF</a></li>
                    <li><a class="dropdown-item text-danger" href="{{ route('pos.receipt.pdf', ['sale' => $sale, 'format' => '58mm']) }}"><i class="bi bi-file-earmark-pdf me-2"></i> 58mm PDF</a></li>
                </ul>
            </div>

            <form action="{{ route('pos.receipt.reprint', $sale) }}" method="POST" target="_blank" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-warning text-dark" title="Record duplicate receipt reprint">
                    <i class="bi bi-arrow-repeat me-1"></i> Reprint
                    @if ($sale->reprint_count > 0)
                        <span class="badge bg-secondary ms-1">#{{ $sale->reprint_count }}</span>
                    @endif
                </button>
            </form>

            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Sales
            </a>
        </div>
    </div>

    <x-card class="mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <small class="text-muted d-block">Customer</small>
                <strong>{{ $sale->customer?->name ?? 'Walk-in Customer' }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Cashier / Staff</small>
                <strong>{{ $sale->user?->name ?? 'Staff' }}</strong>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Payment Method</small>
                <span class="badge bg-light text-dark border text-uppercase">{{ $sale->payment_method }}</span>
            </div>
            <div class="col-md-3">
                <small class="text-muted d-block">Payment Status</small>
                <x-badge :type="$sale->payment_status === 'paid' ? 'success' : 'warning'">{{ ucfirst($sale->payment_status) }}</x-badge>
            </div>
        </div>
    </x-card>

    <x-card title="Order Items">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr>
                            <td>{{ $item->product?->name }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-end fw-bold">${{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Subtotal:</th>
                        <th class="text-end">${{ number_format($sale->subtotal, 2) }}</th>
                    </tr>
                    @if ($sale->discount_amount > 0)
                        <tr>
                            <th colspan="3" class="text-end text-danger">Discount:</th>
                            <th class="text-end text-danger">-${{ number_format($sale->discount_amount, 2) }}</th>
                        </tr>
                    @endif
                    @if ($sale->tax_amount > 0)
                        <tr>
                            <th colspan="3" class="text-end">Tax:</th>
                            <th class="text-end">${{ number_format($sale->tax_amount, 2) }}</th>
                        </tr>
                    @endif
                    <tr class="table-light">
                        <th colspan="3" class="text-end fs-5">Grand Total:</th>
                        <th class="text-end text-primary fs-5">${{ number_format($sale->total_amount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-card>
</div>
@endsection
