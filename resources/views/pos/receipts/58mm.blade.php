<div class="receipt-58mm">
    <!-- Header & Branding -->
    <div class="text-center">
        @if (!empty($store['store_logo']))
            <img src="{{ asset($store['store_logo']) }}" alt="Logo" class="store-logo-58">
        @endif
        <div class="store-name fw-bold">{{ $store['store_name'] }}</div>
        <div class="store-info">{{ $store['store_address'] }}</div>
        <div class="store-info">Tel: {{ $store['store_phone'] }}</div>
        @if (!empty($store['store_email']))
            <div class="store-info">{{ $store['store_email'] }}</div>
        @endif
        @if (!empty($store['store_website']))
            <div class="store-info">{{ $store['store_website'] }}</div>
        @endif
    </div>

    @if ($isReprint)
        <div class="reprint-badge text-center">
            *** DUPLICATE RECEIPT (#{{ $sale->reprint_count }}) ***
        </div>
    @endif

    <div class="divider-dashed"></div>

    <!-- Metadata -->
    <div class="meta-section">
        <div><strong>Inv :</strong> {{ $sale->invoice_no }}</div>
        <div><strong>Date:</strong> {{ $sale->sale_date->format('y-m-d H:i') }}</div>
        <div><strong>Cashier :</strong> {{ $sale->user?->name ?? 'Staff' }}</div>
        <div><strong>Customer:</strong> {{ $sale->customer?->name ?? 'Walk-in' }}</div>
    </div>

    <div class="divider-dashed"></div>

    <!-- Items List -->
    <div class="items-section">
        @foreach ($sale->items as $item)
            <div class="item-row">
                <div class="item-name fw-bold">{{ $item->product?->name ?? 'Item' }}</div>
                <div class="item-details">
                    <span>{{ $item->quantity }} x {{ $store['currency_symbol'] ?? '$' }}{{ number_format($item->unit_price, 2) }}</span>
                    @if ($item->discount > 0)
                        <span class="text-muted">(-{{ $store['currency_symbol'] ?? '$' }}{{ number_format($item->discount, 2) }})</span>
                    @endif
                    <span class="item-total fw-bold">{{ $store['currency_symbol'] ?? '$' }}{{ number_format($item->subtotal, 2) }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="divider-dashed"></div>

    <!-- Financial Totals -->
    <div class="totals-section">
        <div class="d-flex justify-content-between">
            <span>Subtotal:</span>
            <span>{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->subtotal, 2) }}</span>
        </div>
        @if ($sale->discount_amount > 0)
            <div class="d-flex justify-content-between">
                <span>Discount:</span>
                <span>-{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->discount_amount, 2) }}</span>
            </div>
        @endif
        @if ($sale->tax_amount > 0)
            <div class="d-flex justify-content-between">
                <span>Tax:</span>
                <span>{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->tax_amount, 2) }}</span>
            </div>
        @endif
        <div class="d-flex justify-content-between fw-bold grand-total-row">
            <span>TOTAL:</span>
            <span>{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->total_amount, 2) }}</span>
        </div>
        <div class="divider-solid"></div>
        <div class="d-flex justify-content-between">
            <span>Method:</span>
            <span>{{ $sale->payment_method_label }}</span>
        </div>
        <div class="d-flex justify-content-between">
            <span>Paid:</span>
            <span>{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->paid_amount, 2) }}</span>
        </div>
        <div class="d-flex justify-content-between">
            <span>Change:</span>
            <span>{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->change_amount, 2) }}</span>
        </div>
        @if ($sale->due_amount > 0)
            <div class="d-flex justify-content-between fw-bold text-danger">
                <span>Due Balance:</span>
                <span>{{ $store['currency_symbol'] ?? '$' }}{{ number_format($sale->due_amount, 2) }}</span>
            </div>
        @endif
        <div class="d-flex justify-content-between align-items-center mt-1">
            <span>Status:</span>
            <span class="status-tag">{{ $sale->payment_status_label }}</span>
        </div>
    </div>

    <div class="divider-dashed"></div>

    <!-- Footer -->
    <div class="text-center footer-text">
        <div>{{ $store['receipt_footer'] }}</div>
        <div class="mt-1" style="font-size: 9px;">* Powered by OmniPOS *</div>
    </div>
</div>
