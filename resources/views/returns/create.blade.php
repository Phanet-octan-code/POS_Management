@extends('layouts.app')

@section('title', 'Process Product Return')

@section('content')
<div class="container-fluid p-0" style="max-width: 1100px;">
    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Process Product Return</h3>
            <p class="text-muted mb-0">Search sales invoice, select items and quantities to refund, and automatically restock inventory.</p>
        </div>
        <a href="{{ route('returns.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Returns
        </a>
    </div>

    @if (!empty($errorMessage))
        <x-alert type="danger" :message="$errorMessage" class="mb-4" />
    @endif

    <!-- Step 1: Invoice Search Toolbar -->
    <x-card class="shadow-sm border-0 mb-4">
        <div class="row align-items-center g-3">
            <div class="col-md-8">
                <label class="form-label small fw-bold text-muted text-uppercase mb-1">
                    <i class="bi bi-upc-scan me-1 text-primary"></i> 1. Enter or Scan Sales Invoice Number
                </label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-light font-monospace"><i class="bi bi-receipt"></i></span>
                    <input type="text"
                           id="invoiceSearchInput"
                           class="form-control font-monospace fw-bold"
                           placeholder="e.g. INV-2026-000001"
                           value="{{ $prefillInvoice }}"
                           autocomplete="off">
                    <button type="button" class="btn btn-primary px-4 fw-bold" onclick="executeInvoiceLookup()" id="searchInvoiceBtn">
                        <i class="bi bi-search me-1"></i> Lookup Invoice
                    </button>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-2.5 bg-light rounded-3 small text-muted border">
                    <div class="fw-semibold text-dark"><i class="bi bi-shield-check me-1 text-success"></i> Strict Return Rules</div>
                    <ul class="mb-0 ps-3 mt-1" style="font-size: 0.78rem;">
                        <li>Prevents returning more than originally sold.</li>
                        <li>Deducts prior partial returns automatically.</li>
                        <li>Restocks inventory & logs movements.</li>
                    </ul>
                </div>
            </div>
        </div>
    </x-card>

    <!-- Return Form Container (Visible when an invoice is active) -->
    <div id="returnFormContainer" class="{{ empty($initialData) ? 'd-none' : '' }}">
        <form id="productReturnForm" onsubmit="submitProductReturn(event)">
            @csrf
            <input type="hidden" name="sale_id" id="hiddenSaleId" value="{{ $initialData['sale']->id ?? '' }}">

            <!-- Invoice Overview Card -->
            <x-card class="shadow-sm border-0 mb-4 bg-light">
                <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
                    <div>
                        <span class="badge bg-primary text-uppercase px-2.5 py-1 mb-1">Active Invoice</span>
                        <h4 class="fw-bold font-monospace text-dark mb-0" id="invDisplayNo">
                            {{ $initialData['sale']->invoice_no ?? '' }}
                        </h4>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Invoice Date & Time</small>
                        <span class="fw-semibold text-dark" id="invDisplayDate">
                            {{ isset($initialData['sale']) ? $initialData['sale']->sale_date->format('M d, Y H:i') : '' }}
                        </span>
                    </div>
                </div>

                <div class="row g-3 small">
                    <div class="col-6 col-md-3">
                        <span class="text-muted d-block">Customer:</span>
                        <strong class="text-dark fs-6" id="invDisplayCustomer">
                            {{ $initialData['sale']->customer?->name ?? 'Walk-in Customer' }}
                        </strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted d-block">Cashier / Staff:</span>
                        <strong class="text-dark" id="invDisplayCashier">
                            {{ $initialData['sale']->user?->name ?? 'Staff' }}
                        </strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted d-block">Original Total:</span>
                        <strong class="text-dark fs-6" id="invDisplayTotal">
                            ${{ isset($initialData['sale']) ? number_format($initialData['sale']->total_amount, 2) : '0.00' }}
                        </strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted d-block">Payment Method / Status:</span>
                        <span class="badge bg-white text-dark border" id="invDisplayPayment">
                            {{ $initialData['sale']->payment_method_label ?? 'Cash' }}
                        </span>
                        <span class="badge bg-success" id="invDisplayStatus">
                            {{ $initialData['sale']->payment_status_label ?? 'Paid' }}
                        </span>
                    </div>
                </div>
            </x-card>

            <!-- Fully Returned Warning Alert -->
            <div id="fullyReturnedAlert" class="alert alert-warning d-flex align-items-center gap-2 mb-4 {{ empty($initialData['is_fully_returned']) ? 'd-none' : '' }}">
                <i class="bi bi-exclamation-triangle-fill fs-4 text-warning"></i>
                <div>
                    <strong>This invoice has already been fully returned!</strong>
                    <div class="small">All sold quantities on this invoice have reached their maximum cumulative return limits.</div>
                </div>
            </div>

            <!-- Step 2: Select Products to Return -->
            <x-card title="2. Select Products & Return Quantities" class="shadow-sm border-0 mb-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="returnItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;" class="text-center">Select</th>
                                <th style="width: 32%;">Product</th>
                                <th style="width: 12%;" class="text-center">Sold Qty</th>
                                <th style="width: 12%;" class="text-center">Already Returned</th>
                                <th style="width: 12%;" class="text-center">Returnable Qty</th>
                                <th style="width: 15%;" class="text-center">Return Qty</th>
                                <th style="width: 12%;" class="text-end">Unit Price</th>
                                <th style="width: 12%;" class="text-end">Refund Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="returnItemsTableBody">
                            @if (!empty($initialData['items']))
                                @foreach ($initialData['items'] as $item)
                                    @php $disabled = $item['max_returnable'] <= 0; @endphp
                                    <tr class="{{ $disabled ? 'table-light text-muted opacity-75' : '' }}" id="itemRow_{{ $item['product_id'] }}">
                                        <!-- Checkbox -->
                                        <td class="text-center">
                                            <input type="checkbox"
                                                   class="form-check-input item-select-check"
                                                   data-product-id="{{ $item['product_id'] }}"
                                                   {{ $disabled ? 'disabled' : '' }}
                                                   onchange="toggleItemSelect({{ $item['product_id'] }})">
                                        </td>
                                        <!-- Product Details -->
                                        <td>
                                            <div class="fw-bold text-dark">{{ $item['product_name'] }}</div>
                                            <small class="text-muted font-monospace">{{ $item['sku'] }}</small>
                                            @if ($disabled)
                                                <span class="badge bg-secondary ms-1 small">Fully Returned</span>
                                            @endif
                                        </td>
                                        <!-- Sold Qty -->
                                        <td class="text-center">{{ $item['quantity_sold'] }}</td>
                                        <!-- Already Returned Qty -->
                                        <td class="text-center text-danger">{{ $item['previously_returned'] }}</td>
                                        <!-- Max Returnable Qty -->
                                        <td class="text-center fw-bold text-primary max-returnable-val" id="maxQty_{{ $item['product_id'] }}">
                                            {{ $item['max_returnable'] }}
                                        </td>
                                        <!-- Return Qty Input -->
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                <input type="number"
                                                       name="items[{{ $loop->index }}][quantity]"
                                                       id="returnQty_{{ $item['product_id'] }}"
                                                       class="form-control form-control-sm text-center fw-bold return-qty-input"
                                                       style="width: 70px;"
                                                       value="0"
                                                       min="0"
                                                       max="{{ $item['max_returnable'] }}"
                                                       data-price="{{ $item['unit_price'] }}"
                                                       data-max="{{ $item['max_returnable'] }}"
                                                       data-product-id="{{ $item['product_id'] }}"
                                                       {{ $disabled ? 'disabled' : '' }}
                                                       oninput="onReturnQtyChanged({{ $item['product_id'] }})">
                                                <input type="hidden" name="items[{{ $loop->index }}][product_id]" value="{{ $item['product_id'] }}">
                                                <input type="hidden" name="items[{{ $loop->index }}][unit_price]" value="{{ $item['unit_price'] }}">
                                            </div>
                                        </td>
                                        <!-- Unit Price -->
                                        <td class="text-end">${{ number_format($item['unit_price'], 2) }}</td>
                                        <!-- Line Refund Subtotal -->
                                        <td class="text-end fw-bold text-danger line-refund-subtotal" id="lineRefund_{{ $item['product_id'] }}">
                                            $0.00
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </x-card>

            <!-- Step 3: Return Condition, Reason & Refund Summary -->
            <div class="row g-4 mb-4">
                <div class="col-md-7">
                    <x-card title="3. Return Details & Reason" class="shadow-sm border-0 h-100">
                        <!-- Return Reason -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted text-uppercase">Primary Return Reason <span class="text-danger">*</span></label>
                            <select name="reason" id="returnReasonSelect" class="form-select" required>
                                <option value="Customer Changed Mind">Customer Changed Mind</option>
                                <option value="Defective / Faulty Item">Defective / Faulty Item</option>
                                <option value="Wrong Item Purchased">Wrong Item Purchased</option>
                                <option value="Damaged in Packaging">Damaged in Packaging</option>
                                <option value="Expired / Past Date">Expired / Past Date</option>
                                <option value="Other Reason">Other Reason</option>
                            </select>
                        </div>

                        <!-- Item Condition -->
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted text-uppercase">Stock Condition</label>
                            <select id="itemConditionSelect" class="form-select">
                                <option value="resellable" selected>Resellable (Standard Inventory Restock)</option>
                                <option value="damaged">Damaged (Restocked for inspection/write-off)</option>
                                <option value="defective">Defective (Restocked for supplier claim)</option>
                            </select>
                            <small class="text-muted d-block mt-1">Returned items are immediately credited back to store inventory.</small>
                        </div>

                        <!-- Optional Staff Memo -->
                        <div class="mb-0">
                            <label class="form-label small fw-semibold text-muted text-uppercase">Staff Memo / Notes (Optional)</label>
                            <textarea id="returnNotes" class="form-control" rows="2" placeholder="Optional notes about this return..."></textarea>
                        </div>
                    </x-card>
                </div>

                <div class="col-md-5">
                    <x-card title="4. Refund Summary" class="shadow-sm border-0 h-100 bg-light">
                        <div class="p-3 bg-white rounded-3 border mb-3">
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span>Selected Products:</span>
                                <strong class="text-dark" id="summarySelectedCount">0 item(s)</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span>Total Return Units:</span>
                                <strong class="text-dark" id="summaryTotalUnits">0 units</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                <span class="fw-bold text-dark fs-5">Total Refund Due:</span>
                                <h3 class="fw-bold text-danger mb-0" id="summaryTotalRefund">$0.00</h3>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg fw-bold shadow py-2.5" id="submitReturnBtn" disabled>
                                <i class="bi bi-check2-circle me-1"></i> Complete Return & Refund
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetReturnForm()">
                                Cancel / Clear
                            </button>
                        </div>
                    </x-card>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Invoice Lookup
    const invoiceInput = document.getElementById('invoiceSearchInput');

    invoiceInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            executeInvoiceLookup();
        }
    });

    async function executeInvoiceLookup() {
        const invNo = invoiceInput.value.trim();
        if (!invNo) {
            Toast.fire({ icon: 'warning', title: 'Please enter an invoice number' });
            return;
        }

        const btn = document.getElementById('searchInvoiceBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Searching...';

        try {
            const res = await fetch(`{{ route('returns.search-sale') }}?invoice_no=${encodeURIComponent(invNo)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await res.json();

            if (data.success) {
                renderInvoiceReturnUI(data);
                Toast.fire({ icon: 'success', title: `Found Invoice #${data.sale.invoice_no}` });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Invoice Not Found',
                    text: data.message || 'No sale record matches this invoice number.'
                });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to lookup invoice from server.' });
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-search me-1"></i> Lookup Invoice';
        }
    }

    function renderInvoiceReturnUI(data) {
        document.getElementById('returnFormContainer').classList.remove('d-none');
        document.getElementById('hiddenSaleId').value = data.sale.id;

        // Meta headers
        document.getElementById('invDisplayNo').innerText = data.sale.invoice_no;
        document.getElementById('invDisplayDate').innerText = data.sale.sale_date;
        document.getElementById('invDisplayCustomer').innerText = data.sale.customer_name;
        document.getElementById('invDisplayCashier').innerText = data.sale.cashier_name;
        document.getElementById('invDisplayTotal').innerText = `$${data.sale.total_amount}`;
        document.getElementById('invDisplayPayment').innerText = data.sale.payment_method;
        document.getElementById('invDisplayStatus').innerText = data.sale.payment_status;

        // Fully returned banner
        const fullyReturnedAlert = document.getElementById('fullyReturnedAlert');
        if (data.is_fully_returned) {
            fullyReturnedAlert.classList.remove('d-none');
        } else {
            fullyReturnedAlert.classList.add('d-none');
        }

        // Render table rows
        const tbody = document.getElementById('returnItemsTableBody');
        tbody.innerHTML = data.items.map((item, idx) => {
            const disabled = item.max_returnable <= 0;
            return `
                <tr class="${disabled ? 'table-light text-muted opacity-75' : ''}" id="itemRow_${item.product_id}">
                    <td class="text-center">
                        <input type="checkbox"
                               class="form-check-input item-select-check"
                               data-product-id="${item.product_id}"
                               ${disabled ? 'disabled' : ''}
                               onchange="toggleItemSelect(${item.product_id})">
                    </td>
                    <td>
                        <div class="fw-bold text-dark">${item.product_name}</div>
                        <small class="text-muted font-monospace">${item.sku}</small>
                        ${disabled ? '<span class="badge bg-secondary ms-1 small">Fully Returned</span>' : ''}
                    </td>
                    <td class="text-center">${item.quantity_sold}</td>
                    <td class="text-center text-danger">${item.previously_returned}</td>
                    <td class="text-center fw-bold text-primary max-returnable-val" id="maxQty_${item.product_id}">
                        ${item.max_returnable}
                    </td>
                    <td>
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <input type="number"
                                   name="items[${idx}][quantity]"
                                   id="returnQty_${item.product_id}"
                                   class="form-control form-control-sm text-center fw-bold return-qty-input"
                                   style="width: 70px;"
                                   value="0"
                                   min="0"
                                   max="${item.max_returnable}"
                                   data-price="${item.unit_price}"
                                   data-max="${item.max_returnable}"
                                   data-product-id="${item.product_id}"
                                   ${disabled ? 'disabled' : ''}
                                   oninput="onReturnQtyChanged(${item.product_id})">
                            <input type="hidden" name="items[${idx}][product_id]" value="${item.product_id}">
                            <input type="hidden" name="items[${idx}][unit_price]" value="${item.unit_price}">
                        </div>
                    </td>
                    <td class="text-end">$${item.unit_price.toFixed(2)}</td>
                    <td class="text-end fw-bold text-danger line-refund-subtotal" id="lineRefund_${item.product_id}">
                        $0.00
                    </td>
                </tr>
            `;
        }).join('');

        recalculateRefundSummary();
    }

    function toggleItemSelect(productId) {
        const checkbox = document.querySelector(`.item-select-check[data-product-id="${productId}"]`);
        const input = document.getElementById(`returnQty_${productId}`);
        const max = parseInt(input.dataset.max, 10) || 0;

        if (checkbox.checked) {
            input.value = max > 0 ? 1 : 0;
        } else {
            input.value = 0;
        }

        onReturnQtyChanged(productId);
    }

    function onReturnQtyChanged(productId) {
        const input = document.getElementById(`returnQty_${productId}`);
        const checkbox = document.querySelector(`.item-select-check[data-product-id="${productId}"]`);
        const lineRefund = document.getElementById(`lineRefund_${productId}`);

        let qty = parseInt(input.value, 10);
        const max = parseInt(input.dataset.max, 10);
        const price = parseFloat(input.dataset.price);

        if (isNaN(qty) || qty < 0) {
            qty = 0;
            input.value = 0;
        }

        // Strictly prevent returning more quantity than sold
        if (qty > max) {
            Swal.fire({
                icon: 'warning',
                title: 'Quantity Exceeded',
                text: `Cannot return ${qty} units. Maximum returnable quantity is ${max}.`
            });
            qty = max;
            input.value = max;
        }

        // Keep checkbox in sync
        checkbox.checked = qty > 0;

        // Line subtotal
        const lineSubtotal = qty * price;
        lineRefund.innerText = `$${lineSubtotal.toFixed(2)}`;

        recalculateRefundSummary();
    }

    function recalculateRefundSummary() {
        let totalRefund = 0.0;
        let totalUnits = 0;
        let selectedItemsCount = 0;

        document.querySelectorAll('.return-qty-input').forEach(input => {
            const qty = parseInt(input.value, 10) || 0;
            const price = parseFloat(input.dataset.price) || 0.0;

            if (qty > 0) {
                totalUnits += qty;
                totalRefund += (qty * price);
                selectedItemsCount++;
            }
        });

        document.getElementById('summarySelectedCount').innerText = `${selectedItemsCount} product(s)`;
        document.getElementById('summaryTotalUnits').innerText = `${totalUnits} unit(s)`;
        document.getElementById('summaryTotalRefund').innerText = `$${totalRefund.toFixed(2)}`;

        const submitBtn = document.getElementById('submitReturnBtn');
        submitBtn.disabled = totalUnits <= 0;
    }

    async function submitProductReturn(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submitReturnBtn');
        const saleId = document.getElementById('hiddenSaleId').value;
        const reason = document.getElementById('returnReasonSelect').value;
        const condition = document.getElementById('itemConditionSelect').value;
        const notes = document.getElementById('returnNotes').value;

        const returnItems = [];
        document.querySelectorAll('.return-qty-input').forEach(input => {
            const qty = parseInt(input.value, 10) || 0;
            if (qty > 0) {
                returnItems.push({
                    product_id: parseInt(input.dataset.productId, 10),
                    quantity: qty,
                    unit_price: parseFloat(input.dataset.price),
                    condition: condition
                });
            }
        });

        if (returnItems.length === 0) {
            Toast.fire({ icon: 'warning', title: 'Please select at least one item to return' });
            return;
        }

        const confirmResult = await Swal.fire({
            title: 'Confirm Product Return?',
            text: `Process refund of ${document.getElementById('summaryTotalRefund').innerText} and restock ${document.getElementById('summaryTotalUnits').innerText}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, complete return',
            cancelButtonText: 'Cancel'
        });

        if (!confirmResult.isConfirmed) return;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        try {
            const res = await fetch('{{ route("returns.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    sale_id: saleId,
                    reason: reason + (notes ? ` - ${notes}` : ''),
                    items: returnItems
                })
            });

            const data = await res.json();

            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Return Completed!',
                    text: `Return #${data.return_number} recorded. Total refund: $${data.total_refund}. Inventory restocked.`,
                    confirmButtonText: 'View Return Slip'
                }).then(() => {
                    window.location.href = data.redirect_url;
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Return Failed',
                    text: data.message || 'Could not process return.'
                });
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Complete Return & Refund';
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Server Error', text: 'A network error occurred while submitting the return.' });
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Complete Return & Refund';
        }
    }

    function resetReturnForm() {
        document.getElementById('returnFormContainer').classList.add('d-none');
        invoiceInput.value = '';
        invoiceInput.focus();
    }
</script>
@endpush
