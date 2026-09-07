<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $sale->invoice_no }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 15px;
            max-width: 330px;
            margin: 0 auto;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .dashed-line { border-bottom: 1px dashed #000; margin: 10px 0; }
        .table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .table th, .table td { padding: 4px 0; }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
            border: 1px solid #000;
            border-radius: 3px;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="text-center">
        <h2 style="margin: 0; font-size: 20px; letter-spacing: 1px;">{{ config('app.name', 'POS MANAGEMENT') }}</h2>
        <p style="margin: 2px 0; font-size: 12px;">Official Sales Receipt</p>
        <p style="margin: 2px 0; font-size: 11px;">Phnom Penh, Cambodia</p>
    </div>

    <div class="dashed-line"></div>

    <div style="font-size: 12px; line-height: 1.5;">
        <div><strong>Invoice No :</strong> {{ $sale->invoice_no }}</div>
        <div><strong>Date & Time:</strong> {{ $sale->sale_date->format('Y-m-d H:i:s') }}</div>
        <div><strong>Cashier   :</strong> {{ $sale->user?->name ?? 'Staff' }}</div>
        <div><strong>Customer  :</strong> {{ $sale->customer?->name ?? 'Walk-in Customer' }}</div>
    </div>

    <div class="dashed-line"></div>

    <table class="table">
        <thead>
            <tr style="border-bottom: 1px dashed #000;">
                <th class="text-left" style="width: 45%;">Item</th>
                <th class="text-center" style="width: 15%;">Qty</th>
                <th class="text-right" style="width: 20%;">Price</th>
                <th class="text-right" style="width: 20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td class="text-left" style="vertical-align: top;">{{ $item->product?->name ?? 'Item' }}</td>
                    <td class="text-center" style="vertical-align: top;">{{ $item->quantity }}</td>
                    <td class="text-right" style="vertical-align: top;">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right" style="vertical-align: top;">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="dashed-line"></div>

    <div style="font-size: 13px; line-height: 1.5;">
        <div style="display: flex; justify-content: space-between;">
            <span>Subtotal:</span>
            <span>${{ number_format($sale->subtotal, 2) }}</span>
        </div>
        @if ($sale->discount_amount > 0)
            <div style="display: flex; justify-content: space-between;">
                <span>Discount:</span>
                <span>-${{ number_format($sale->discount_amount, 2) }}</span>
            </div>
        @endif
        @if ($sale->tax_amount > 0)
            <div style="display: flex; justify-content: space-between;">
                <span>Tax:</span>
                <span>${{ number_format($sale->tax_amount, 2) }}</span>
            </div>
        @endif
        <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; margin-top: 6px; padding-top: 4px; border-top: 1px solid #000;">
            <span>TOTAL:</span>
            <span>${{ number_format($sale->total_amount, 2) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 6px;">
            <span>Payment Method:</span>
            <span class="fw-bold">{{ $sale->payment_method_label }}</span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>Amount Paid:</span>
            <span>${{ number_format($sale->paid_amount, 2) }}</span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>Change:</span>
            <span>${{ number_format($sale->change_amount, 2) }}</span>
        </div>
        @if ($sale->due_amount > 0)
            <div style="display: flex; justify-content: space-between; font-weight: bold;">
                <span>Remaining Balance:</span>
                <span>${{ number_format($sale->due_amount, 2) }}</span>
            </div>
        @endif
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px; padding-top: 4px; border-top: 1px dashed #000;">
            <span class="fw-bold">Payment Status:</span>
            <span class="status-badge">{{ $sale->payment_status_label }}</span>
        </div>
    </div>

    <div class="dashed-line"></div>

    <div class="text-center" style="margin-top: 15px;">
        <p style="margin: 3px 0; font-weight: bold;">*** Thank You! Please Come Again ***</p>
        <p style="margin: 3px 0; font-size: 11px;">Goods sold are eligible for return within 7 days with this invoice.</p>
    </div>

    <div class="no-print" style="margin-top: 25px; text-align: center; display: flex; gap: 8px; justify-content: center;">
        <button onclick="window.print()" style="padding: 8px 16px; font-weight: bold; cursor: pointer; background: #0d6efd; color: #fff; border: none; border-radius: 4px;">
            Print Receipt
        </button>
        <button onclick="window.close()" style="padding: 8px 16px; cursor: pointer; background: #6c757d; color: #fff; border: none; border-radius: 4px;">
            Close
        </button>
    </div>
</body>
</html>
