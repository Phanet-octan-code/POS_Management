@extends('layouts.app')

@section('title', 'New Purchase Order')

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
                <span class="text-muted small fw-semibold">New Order</span>
            </div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-plus-circle me-2 text-primary"></i> Create Purchase Order
            </h3>
            <p class="text-muted mb-0">Procure inventory from vendors and automatically synchronize product stock.</p>
        </div>
        <a href="{{ route('purchases.index') }}" class="btn btn-secondary rounded-pill px-3 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Purchases
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger rounded-4 shadow-sm mb-4">
            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Please correct the following errors:</h6>
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('purchases.store') }}" method="POST" id="purchaseForm">
        @csrf

        <!-- Section 1: Order Meta & Supplier Selection -->
        <x-card class="mb-4">
            <h5 class="fw-bold text-dark mb-3">
                <i class="bi bi-file-earmark-text me-2 text-primary"></i> Purchase Information
            </h5>

            <div class="row g-3">
                <!-- Supplier Selection -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-uppercase">Supplier / Vendor <span class="text-danger">*</span></label>
                    <select name="supplier_id" id="supplier_id" class="form-select" required>
                        <option value="">-- Choose Supplier --</option>
                        @foreach ($suppliers as $sup)
                            <option value="{{ $sup->id }}" {{ old('supplier_id') == $sup->id ? 'selected' : '' }}>
                                {{ $sup->name }} ({{ $sup->company_name ?? 'Individual' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Purchase Date -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-uppercase">Purchase Date <span class="text-danger">*</span></label>
                    <input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', date('Y-m-d')) }}" required>
                </div>

                <!-- Reference Number -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-uppercase">Reference No.</label>
                    <input type="text" name="reference_no" class="form-control font-monospace" value="{{ old('reference_no', $generatedRefNo) }}" placeholder="e.g. PO-2026-001">
                </div>

                <!-- Goods Status -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-uppercase">Goods Status <span class="text-danger">*</span></label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="received" {{ old('status') === 'received' ? 'selected' : '' }}>Received (Stock added immediately)</option>
                        <option value="ordered" {{ old('status') === 'ordered' ? 'selected' : '' }}>Ordered (Awaiting delivery)</option>
                        <option value="pending" {{ old('status') === 'pending' ? 'selected' : '' }}>Pending (Draft)</option>
                    </select>
                    <small class="text-muted d-block mt-1">
                        <i class="bi bi-info-circle me-1"></i> Marked as <strong>Received</strong> adds items to inventory.
                    </small>
                </div>

                <!-- Payment Method -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-uppercase">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Wire / Transfer</option>
                        <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>Credit / Debit Card</option>
                        <option value="cheque" {{ old('payment_method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                        <option value="other" {{ old('payment_method') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>

                <!-- Payment Status -->
                <div class="col-md-4">
                    <label class="form-label fw-semibold small text-uppercase">Payment Status <span class="text-danger">*</span></label>
                    <select name="payment_status" id="payment_status" class="form-select" required>
                        <option value="paid" {{ old('payment_status') === 'paid' ? 'selected' : '' }}>Paid in Full</option>
                        <option value="partial" {{ old('payment_status') === 'partial' ? 'selected' : '' }}>Partial Payment</option>
                        <option value="unpaid" {{ old('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid / Due</option>
                    </select>
                </div>
            </div>
        </x-card>

        <!-- Section 2: Product Line Items -->
        <x-card class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="bi bi-cart-plus me-2 text-primary"></i> Line Items & Products
                </h5>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="addRow()">
                    <i class="bi bi-plus-lg me-1"></i> Add Another Item
                </button>
            </div>

            <div class="table-responsive">
                <table class="table align-middle" id="itemsTable">
                    <thead class="table-light text-uppercase small fw-bold">
                        <tr>
                            <th style="min-width: 280px;">Product <span class="text-danger">*</span></th>
                            <th style="width: 140px;" class="text-end">Unit Cost ($) <span class="text-danger">*</span></th>
                            <th style="width: 130px;" class="text-center">Quantity <span class="text-danger">*</span></th>
                            <th style="width: 150px;" class="text-end">Subtotal ($)</th>
                            <th style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsContainer">
                        <!-- Initial Row -->
                        <tr class="item-row" data-index="0">
                            <td>
                                <select name="items[0][product_id]" class="form-select product-select" required onchange="onProductChange(this)">
                                    <option value="">-- Choose Product --</option>
                                    @foreach ($products as $pr)
                                        <option value="{{ $pr->id }}"
                                                data-cost="{{ $pr->cost_price }}"
                                                data-stock="{{ $pr->stock_quantity }}"
                                                data-sku="{{ $pr->sku }}"
                                                data-unit="{{ $pr->unit }}">
                                            {{ $pr->name }} (SKU: {{ $pr->sku }} | Stock: {{ $pr->stock_quantity }} {{ $pr->unit }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted product-stock-info mt-1 d-block"></small>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="items[0][unit_cost]" class="form-control text-end unit-cost-input" value="0.00" required oninput="calculateTotals()">
                            </td>
                            <td>
                                <input type="number" min="1" name="items[0][quantity]" class="form-control text-center quantity-input" value="1" required oninput="calculateTotals()">
                            </td>
                            <td class="text-end fw-bold fs-6 line-total-display">$0.00</td>
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="removeRow(this)" title="Remove item">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-card>

        <!-- Section 3: Order Totals, Tax, Discount & Payment -->
        <div class="row g-4 mb-4">
            <div class="col-lg-7">
                <x-card class="h-100">
                    <h5 class="fw-bold text-dark mb-3">Order Notes & Details</h5>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-uppercase">Remarks / Notes</label>
                        <textarea name="notes" class="form-control" rows="4" placeholder="Add any details regarding shipping, vendor invoice numbers, warranty or terms...">{{ old('notes') }}</textarea>
                    </div>
                </x-card>
            </div>

            <div class="col-lg-5">
                <x-card class="h-100 bg-light">
                    <h5 class="fw-bold text-dark mb-3">Financial Summary</h5>
                    <ul class="list-group list-group-flush bg-transparent">
                        <!-- Subtotal -->
                        <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted fw-semibold">Items Subtotal:</span>
                            <span class="fw-bold text-dark" id="display_subtotal">$0.00</span>
                        </li>

                        <!-- Tax Amount -->
                        <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted fw-semibold">Order Tax ($):</span>
                            <div style="width: 140px;">
                                <input type="number" step="0.01" min="0" name="tax_amount" id="tax_amount" class="form-control form-control-sm text-end" value="{{ old('tax_amount', '0.00') }}" oninput="calculateTotals()">
                            </div>
                        </li>

                        <!-- Discount Amount -->
                        <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted fw-semibold">Order Discount ($):</span>
                            <div style="width: 140px;">
                                <input type="number" step="0.01" min="0" name="discount_amount" id="discount_amount" class="form-control form-control-sm text-end" value="{{ old('discount_amount', '0.00') }}" oninput="calculateTotals()">
                            </div>
                        </li>

                        <!-- Grand Total -->
                        <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-3 border-top border-dark-subtle">
                            <span class="fs-6 fw-bold text-dark">Grand Total:</span>
                            <span class="fs-4 fw-bold text-primary" id="display_grand_total">$0.00</span>
                        </li>

                        <!-- Paid Amount -->
                        <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-2 border-top">
                            <span class="text-muted fw-semibold">Amount Paid Now ($):</span>
                            <div style="width: 140px;">
                                <input type="number" step="0.01" min="0" name="paid_amount" id="paid_amount" class="form-control form-control-sm text-end text-success fw-bold" value="{{ old('paid_amount', '0.00') }}" oninput="calculateTotals()">
                            </div>
                        </li>

                        <!-- Balance Due -->
                        <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted fw-semibold">Balance Due (Owed):</span>
                            <span class="fw-bold text-danger fs-6" id="display_balance_due">$0.00</span>
                        </li>
                    </ul>
                </x-card>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="{{ route('purchases.index') }}" class="btn btn-secondary rounded-pill px-4">Cancel</a>
            <button type="submit" class="btn btn-primary rounded-pill px-5 shadow fw-semibold">
                <i class="bi bi-check2-circle me-1"></i> Save Purchase Order
            </button>
        </div>
    </form>
</div>

<!-- Product options template for new rows -->
<template id="productOptionsTemplate">
    <option value="">-- Choose Product --</option>
    @foreach ($products as $pr)
        <option value="{{ $pr->id }}"
                data-cost="{{ $pr->cost_price }}"
                data-stock="{{ $pr->stock_quantity }}"
                data-sku="{{ $pr->sku }}"
                data-unit="{{ $pr->unit }}">
            {{ $pr->name }} (SKU: {{ $pr->sku }} | Stock: {{ $pr->stock_quantity }} {{ $pr->unit }})
        </option>
    @endforeach
</template>

@push('scripts')
<script>
    let rowIndex = 1;

    function addRow() {
        const container = document.getElementById('itemsContainer');
        const templateOptions = document.getElementById('productOptionsTemplate').innerHTML;

        const tr = document.createElement('tr');
        tr.className = 'item-row';
        tr.dataset.index = rowIndex;
        tr.innerHTML = `
            <td>
                <select name="items[${rowIndex}][product_id]" class="form-select product-select" required onchange="onProductChange(this)">
                    ${templateOptions}
                </select>
                <small class="text-muted product-stock-info mt-1 d-block"></small>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="items[${rowIndex}][unit_cost]" class="form-control text-end unit-cost-input" value="0.00" required oninput="calculateTotals()">
            </td>
            <td>
                <input type="number" min="1" name="items[${rowIndex}][quantity]" class="form-control text-center quantity-input" value="1" required oninput="calculateTotals()">
            </td>
            <td class="text-end fw-bold fs-6 line-total-display">$0.00</td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm border-0" onclick="removeRow(this)" title="Remove item">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        container.appendChild(tr);
        rowIndex++;
    }

    function removeRow(btn) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length <= 1) {
            Swal.fire({
                icon: 'info',
                title: 'Item Required',
                text: 'A purchase order must have at least one product line item.'
            });
            return;
        }
        btn.closest('tr').remove();
        calculateTotals();
    }

    function onProductChange(select) {
        const row = select.closest('tr');
        const opt = select.options[select.selectedIndex];
        const costInput = row.querySelector('.unit-cost-input');
        const stockInfo = row.querySelector('.product-stock-info');

        if (!select.value) {
            costInput.value = '0.00';
            stockInfo.textContent = '';
            calculateTotals();
            return;
        }

        const cost = parseFloat(opt.dataset.cost) || 0;
        const stock = opt.dataset.stock || 0;
        const unit = opt.dataset.unit || 'units';

        costInput.value = cost.toFixed(2);
        stockInfo.innerHTML = `<span class="badge bg-light text-dark border">Current Inventory: ${stock} ${unit}</span>`;
        calculateTotals();
    }

    function calculateTotals() {
        let subtotal = 0.0;
        const rows = document.querySelectorAll('.item-row');

        rows.forEach(row => {
            const cost = parseFloat(row.querySelector('.unit-cost-input').value) || 0.0;
            const qty = parseInt(row.querySelector('.quantity-input').value, 10) || 0;
            const lineTotal = cost * qty;
            row.querySelector('.line-total-display').textContent = '$' + lineTotal.toFixed(2);
            subtotal += lineTotal;
        });

        const tax = parseFloat(document.getElementById('tax_amount').value) || 0.0;
        const discount = parseFloat(document.getElementById('discount_amount').value) || 0.0;
        const grandTotal = Math.max(0, (subtotal + tax) - discount);

        const paidInput = document.getElementById('paid_amount');
        let paid = parseFloat(paidInput.value) || 0.0;

        // Auto-suggest paid amount if user hasn't modified it yet
        if (paid === 0 && grandTotal > 0 && document.getElementById('status').value === 'received') {
            // keep paid as is unless user inputs
        }

        const due = Math.max(0, grandTotal - paid);

        document.getElementById('display_subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('display_grand_total').textContent = '$' + grandTotal.toFixed(2);
        document.getElementById('display_balance_due').textContent = '$' + due.toFixed(2);

        // Auto-update payment status dropdown
        const payStatusSelect = document.getElementById('payment_status');
        if (paid >= grandTotal && grandTotal > 0) {
            payStatusSelect.value = 'paid';
        } else if (paid > 0) {
            payStatusSelect.value = 'partial';
        } else {
            payStatusSelect.value = 'unpaid';
        }
    }

    // Run initial calculation on page load
    document.addEventListener('DOMContentLoaded', calculateTotals);
</script>
@endpush
@endsection
