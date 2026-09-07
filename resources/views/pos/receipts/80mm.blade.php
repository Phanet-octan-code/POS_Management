<div class="receipt-80mm">
    <!-- Store Header & Logo -->
    <div class="text-center header-section">
        @if (!empty($store['store_logo']))
            <img src="{{ asset($store['store_logo']) }}" alt="Logo" class="store-logo-80">
        @endif
        <h3 class="store-name fw-bold mb-0">{{ $store['store_name'] }}</h3>
        <p class="store-info mb-0">{{ $store['store_address'] }}</p>
        <p class="store-info mb-0">Phone: {{ $store['store_phone'] }}</p>
        @if (!empty($store['store_email']))
            <p class="store-info mb-0">Email: {{ $store['store_email'] }}</p>
        @endif
        @if (!empty($store['store_website']))
            <p class="store-info mb-0">{{ $store['store_website'] }}</p>
        @endif
    </div>

    @if ($isReprint)
        <div class="reprint-badge text-center">
            *** DUPLICATE / REPRINT RECEIPT (Count: {{ $sale->reprint_count }}) ***
        </div>
    @endif

    <div class="divider-dashed"></div>

    <!-- Invoice Details Table -->
    <div class="meta-section">
        <table class="w-100 meta-table">
            <tr>
                <td style="width: 50%;"><strong>Invoice No:</strong> {{ $sale->invoice_no }}</td>
                <td style="width: 50%; text-align: right;"><strong>Date:</strong> {{ $sale->sale_date->format('Y-m-d H:i') }}</td>
            </tr>
            <tr>
                <td><strong>Cashier:</strong> {{ $sale->user?->name ?? 'Staff' }}</td>
                <td style="text-align: right;"><strong>Customer:</strong> {{ $sale->customer?->name ?? 'Walk-in Customer' }}</td>
            </tr>
        </table>
    </div>

    <div class="divider-dashed"></div>

    <!-- Items Table -->
    <table class="items-table w-100">
        <thead>
            <tr class="table-header">
                <th style="width: 48%; text-align: left;">Item</th>
                <th style="width: 14%; text-align: center;">Qty</th>
                <th style="width: 18%; text-align: right;">Price</th>
                <th style="width: 20%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td class="text-left">
                        <div class="item-name fw-bold">{{ $item->product?->name ?? 'Product' }}</div>
                        <small class="text-muted">{{ $item->product?->sku ?? '' }}</small>
                    </td>
                    <td class="text-center align-top">{{ $item->quantity }}</td>
                    <td class="text-right align-top">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right align-top fw-bold">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider-dashed"></div>

    <!-- Financial Totals -->
    <div class="totals-section">
        <table class="w-100 totals-table">
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->subtotal, 2) }}</td>
            </tr>
            @if ($sale->discount_amount > 0)
                <tr class="text-danger">
                    <td>Discount:</td>
                    <td class="text-right">-{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->discount_amount, 2) }}</td>
                </tr>
            @endif
            @if ($sale->tax_amount > 0)
                <tr>
                    <td>Tax:</td>
                    <td class="text-right">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->tax_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="grand-total-row">
                <td class="fw-bold fs-6">GRAND TOTAL:</td>
                <td class="text-right fw-bold fs-6">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->total_amount, 2) }}</td>
            </tr>
            <tr class="border-top-solid">
                <td>Payment Method:</td>
                <td class="text-right fw-bold">{{ $sale->payment_method_label }}</td>
            </tr>
            <tr>
                <td>Amount Paid:</td>
                <td class="text-right">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->paid_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Change:</td>
                <td class="text-right fw-bold text-success">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->change_amount, 2) }}</td>
            </tr>
            @if ($sale->due_amount > 0)
                <tr class="fw-bold text-danger">
                    <td>Remaining Balance:</td>
                    <td class="text-right">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->due_amount, 2) }}</td>
                </tr>
            @endif
            <tr class="border-top-dashed">
                <td class="fw-bold">Payment Status:</td>
                <td class="text-right">
                    <span class="status-badge">{{ $sale->payment_status_label }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="divider-dashed"></div>

    <!-- Barcode & Footer -->
    <div class="text-center footer-section">
        <div class="barcode-box">
            <div class="barcode-string font-monospace fw-bold">{{ $sale->invoice_no }}</div>
        </div>
        <p class="footer-msg mt-2 mb-1">{{ $store['receipt_footer'] }}</p>
        <small class="text-muted" style="font-size: 10px;">*** Thank you for shopping with us! ***</small>
    </div>
</div>
