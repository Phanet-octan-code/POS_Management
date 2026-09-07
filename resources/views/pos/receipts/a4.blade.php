<div class="receipt-a4">
    <!-- Top Header -->
    <div class="a4-header d-flex justify-content-between align-items-start pb-4 mb-4 border-bottom">
        <div>
            @if (!empty($store['store_logo']))
                <img src="{{ asset($store['store_logo']) }}" alt="Logo" class="a4-logo mb-2">
            @endif
            <h2 class="fw-bold text-dark mb-1">{{ $store['store_name'] }}</h2>
            <p class="text-muted mb-0">{{ $store['store_address'] }}</p>
            <p class="text-muted mb-0">Phone: {{ $store['store_phone'] }} @if (!empty($store['store_email'])) | {{ $store['store_email'] }} @endif @if (!empty($store['store_website'])) | {{ $store['store_website'] }} @endif</p>
        </div>
        <div class="text-end">
            <h1 class="fw-bold text-primary text-uppercase mb-1" style="letter-spacing: 2px;">INVOICE</h1>
            <div class="fs-5 fw-bold font-monospace text-dark">{{ $sale->invoice_no }}</div>
            <div class="text-muted small">Date: <strong>{{ $sale->sale_date->format('F d, Y - H:i') }}</strong></div>
            <div class="text-muted small">Cashier: <strong>{{ $sale->user?->name ?? 'Staff' }}</strong></div>
            @if ($isReprint)
                <div class="badge bg-warning text-dark border mt-2 px-3 py-1 fw-bold">
                    DUPLICATE COPY (Reprint #{{ $sale->reprint_count }})
                </div>
            @endif
        </div>
    </div>

    <!-- Customer & Bill-To Section -->
    <div class="row g-4 mb-4">
        <div class="col-6">
            <div class="card bg-light border-0 rounded-3 p-3 h-100">
                <small class="text-muted text-uppercase fw-bold mb-1 d-block" style="font-size: 0.75rem;">Billed To (Customer):</small>
                <h5 class="fw-bold text-dark mb-1">{{ $sale->customer?->name ?? 'Walk-in Customer' }}</h5>
                @if ($sale->customer)
                    <p class="small text-muted mb-1">Phone: {{ $sale->customer->phone ?? 'N/A' }}</p>
                    <p class="small text-muted mb-1">Email: {{ $sale->customer->email ?? 'N/A' }}</p>
                    <p class="small text-muted mb-0">Address: {{ $sale->customer->address ?? 'N/A' }}</p>
                    <span class="badge bg-secondary-subtle text-secondary border mt-2 align-self-start">{{ $sale->customer->type }} Customer</span>
                @else
                    <p class="small text-muted mb-0">Over-the-counter POS retail transaction.</p>
                @endif
            </div>
        </div>
        <div class="col-6">
            <div class="card bg-light border-0 rounded-3 p-3 h-100">
                <small class="text-muted text-uppercase fw-bold mb-1 d-block" style="font-size: 0.75rem;">Payment Information:</small>
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">Payment Method:</span>
                    <strong class="text-uppercase">{{ $sale->payment_method_label }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">Payment Status:</span>
                    <span class="badge {{ $sale->payment_status === 'paid' ? 'bg-success' : ($sale->payment_status === 'partial' ? 'bg-warning text-dark' : 'bg-danger') }}">
                        {{ $sale->payment_status_label }}
                    </span>
                </div>
                <div class="d-flex justify-content-between mb-1 small">
                    <span class="text-muted">Amount Paid:</span>
                    <strong>{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->paid_amount, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between small">
                    <span class="text-muted">Change Returned:</span>
                    <strong class="text-success">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->change_amount, 2) }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle mb-0 a4-table">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 45%;">Product Description</th>
                    <th style="width: 15%;">SKU</th>
                    <th style="width: 10%;" class="text-center">Quantity</th>
                    <th style="width: 12%;" class="text-end">Unit Price</th>
                    <th style="width: 13%;" class="text-end">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $idx => $item)
                    <tr>
                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $item->product?->name ?? 'Product' }}</div>
                            @if ($item->discount > 0)
                                <small class="text-danger">Item Discount: -{{ $store['currency_symbol'] ?? '$' }}{{ number_format($item->discount, 2) }}</small>
                            @endif
                        </td>
                        <td class="font-monospace small text-muted">{{ $item->product?->sku ?? '-' }}</td>
                        <td class="text-center fw-semibold">{{ $item->quantity }} {{ $item->product?->unit ?? '' }}</td>
                        <td class="text-end">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end fw-bold">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Totals & Summary Block -->
    <div class="row g-4 mb-5">
        <div class="col-7">
            <div class="p-3 border rounded-3 bg-light-subtle h-100">
                <h6 class="fw-bold text-dark mb-2">Terms & Notes:</h6>
                <p class="small text-muted mb-2">{{ $store['receipt_footer'] }}</p>
                <p class="small text-muted mb-0">For queries or returns, contact support at <strong>{{ $store['store_phone'] }}</strong> @if (!empty($store['store_email'])) or email <strong>{{ $store['store_email'] }}</strong> @endif.</p>
                @if ($sale->notes)
                    <div class="mt-2 pt-2 border-top small text-secondary">
                        <strong>Order Memo:</strong> {{ $sale->notes }}
                    </div>
                @endif
            </div>
        </div>
        <div class="col-5">
            <div class="card border rounded-3 p-3">
                <table class="table table-sm table-borderless mb-0 small">
                    <tr>
                        <td class="text-muted">Subtotal:</td>
                        <td class="text-end fw-semibold">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->subtotal, 2) }}</td>
                    </tr>
                    @if ($sale->discount_amount > 0)
                        <tr class="text-danger">
                            <td>Discount Amount:</td>
                            <td class="text-end fw-semibold">-{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if ($sale->tax_amount > 0)
                        <tr>
                            <td class="text-muted">Tax / VAT:</td>
                            <td class="text-end fw-semibold">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr class="border-top border-dark-subtle">
                        <td class="fs-5 fw-bold text-dark pt-2">Grand Total:</td>
                        <td class="fs-5 fw-bold text-primary text-end pt-2">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted pt-2">Amount Paid ({{ $sale->payment_method_label }}):</td>
                        <td class="text-end fw-bold pt-2">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->paid_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Change:</td>
                        <td class="text-end fw-bold text-success">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->change_amount, 2) }}</td>
                    </tr>
                    @if ($sale->due_amount > 0)
                        <tr class="border-top text-danger fw-bold">
                            <td class="pt-2">Remaining Balance:</td>
                            <td class="text-end pt-2">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->due_amount, 2) }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="row pt-4 mt-5 text-center a4-signatures">
        <div class="col-6">
            <div class="signature-line mx-auto" style="width: 200px; border-top: 1px dashed #999; margin-top: 50px;"></div>
            <small class="text-muted d-block mt-1">Customer Signature</small>
        </div>
        <div class="col-6">
            <div class="signature-line mx-auto" style="width: 200px; border-top: 1px dashed #999; margin-top: 50px;"></div>
            <small class="text-muted d-block mt-1">Authorized Cashier ({{ $sale->user?->name ?? 'Staff' }})</small>
        </div>
    </div>
</div>
