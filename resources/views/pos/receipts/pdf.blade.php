<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - {{ $sale->invoice_no }}</title>
    @php
        $store = $store ?? [
            'store_name' => 'OmniPOS Superstore',
            'store_address' => '100 Downtown Boulevard, Metropolis',
            'store_phone' => '+1 (555) 019-2831',
            'receipt_footer' => 'Thank you for your purchase! Returns accepted within 14 days with original receipt.',
        ];
    @endphp
    <style>
        @page {
            margin: {{ $format === 'a4' ? '15mm' : '3mm' }};
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: {{ $format === '58mm' ? '9px' : ($format === '80mm' ? '11px' : '12px') }};
            line-height: 1.35;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .w-100 { width: 100%; }

        .divider-dashed {
            border-bottom: 1px dashed #000;
            margin: 6px 0;
        }
        .divider-solid {
            border-bottom: 1px solid #000;
            margin: 5px 0;
        }

        .reprint-box {
            border: 1px solid #000;
            padding: 2px 4px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            margin: 4px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        /* 58mm / 80mm Table Styling */
        .thermal-table th, .thermal-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        /* A4 Specific Styling */
        .a4-table {
            border: 1px solid #cbd5e1;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .a4-table th {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 8px;
            font-size: 11px;
            text-transform: uppercase;
        }
        .a4-table td {
            border: 1px solid #cbd5e1;
            padding: 8px;
            font-size: 11px;
        }
        .a4-card {
            border: 1px solid #cbd5e1;
            padding: 10px;
            background: #f8fafc;
            border-radius: 4px;
        }
    </style>
</head>
<body>

@if ($format === 'a4')
    <!-- ==================== A4 INVOICE PDF LAYOUT ==================== -->
    <table style="width: 100%; margin-bottom: 15px;">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                @if (!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" style="max-height: 45px; margin-bottom: 6px;">
                @endif
                <div style="font-size: 18px; font-weight: bold;">{{ $store['store_name'] }}</div>
                <div style="color: #475569;">{{ $store['store_address'] }}</div>
                <div style="color: #475569;">Tel: {{ $store['store_phone'] }}</div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <h1 style="margin: 0; color: #0d6efd; font-size: 24px; text-transform: uppercase;">INVOICE</h1>
                <div style="font-size: 14px; font-weight: bold; margin-top: 4px;">{{ $sale->invoice_no }}</div>
                <div style="color: #475569;">Date: {{ $sale->sale_date->format('Y-m-d H:i') }}</div>
                <div style="color: #475569;">Cashier: {{ $sale->user?->name ?? 'Staff' }}</div>
                @if ($isReprint)
                    <div class="reprint-box" style="display: inline-block; margin-top: 4px;">
                        *** DUPLICATE (Reprint #{{ $sale->reprint_count }}) ***
                    </div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Customer & Payment Summary Cards -->
    <table style="width: 100%; margin-bottom: 15px;">
        <tr>
            <td style="width: 48%; vertical-align: top;">
                <div class="a4-card">
                    <strong style="text-transform: uppercase; font-size: 10px; color: #64748b;">Customer Information</strong>
                    <div style="font-size: 13px; font-weight: bold; margin-top: 3px;">{{ $sale->customer?->name ?? 'Walk-in Customer' }}</div>
                    @if ($sale->customer)
                        <div>Phone: {{ $sale->customer->phone ?? 'N/A' }}</div>
                        <div>Email: {{ $sale->customer->email ?? 'N/A' }}</div>
                        <div>Address: {{ $sale->customer->address ?? 'N/A' }}</div>
                    @else
                        <div style="color: #64748b;">Over-the-counter POS retail transaction.</div>
                    @endif
                </div>
            </td>
            <td style="width: 4%;"></td>
            <td style="width: 48%; vertical-align: top;">
                <div class="a4-card">
                    <strong style="text-transform: uppercase; font-size: 10px; color: #64748b;">Payment Breakdown</strong>
                    <div>Payment Method: <strong>{{ $sale->payment_method_label }}</strong></div>
                    <div>Payment Status: <strong>{{ $sale->payment_status_label }}</strong></div>
                    <div>Amount Paid: <strong>${{ number_format($sale->paid_amount, 2) }}</strong></div>
                    <div>Change: <strong>${{ number_format($sale->change_amount, 2) }}</strong></div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Table of Items -->
    <table class="a4-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">#</th>
                <th style="width: 45%; text-align: left;">Product Description</th>
                <th style="width: 15%; text-align: left;">SKU</th>
                <th style="width: 10%; text-align: center;">Qty</th>
                <th style="width: 12%; text-align: right;">Price</th>
                <th style="width: 13%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $idx => $item)
                <tr>
                    <td style="text-align: center;">{{ $idx + 1 }}</td>
                    <td>
                        <strong>{{ $item->product?->name ?? 'Item' }}</strong>
                        @if ($item->discount > 0)
                            <div style="color: #dc2626; font-size: 9px;">Discount: -${{ number_format($item->discount, 2) }}</div>
                        @endif
                    </td>
                    <td>{{ $item->product?->sku ?? '-' }}</td>
                    <td style="text-align: center;">{{ $item->quantity }}</td>
                    <td style="text-align: right;">${{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right; font-weight: bold;">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary Box -->
    <table style="width: 100%;">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div style="border: 1px solid #cbd5e1; padding: 10px; border-radius: 4px; font-size: 11px;">
                    <strong>Terms & Notes:</strong>
                    <p style="margin: 4px 0;">{{ $store['receipt_footer'] }}</p>
                    @if ($sale->notes)
                        <div><strong>Order Memo:</strong> {{ $sale->notes }}</div>
                    @endif
                </div>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table style="width: 100%; border: 1px solid #cbd5e1; padding: 10px;">
                    <tr>
                        <td style="padding: 3px 6px;">Subtotal:</td>
                        <td style="padding: 3px 6px; text-align: right;">${{ number_format($sale->subtotal, 2) }}</td>
                    </tr>
                    @if ($sale->discount_amount > 0)
                        <tr>
                            <td style="padding: 3px 6px; color: #dc2626;">Discount:</td>
                            <td style="padding: 3px 6px; text-align: right; color: #dc2626;">-${{ number_format($sale->discount_amount, 2) }}</td>
                        </tr>
                    @endif
                    @if ($sale->tax_amount > 0)
                        <tr>
                            <td style="padding: 3px 6px;">Tax:</td>
                            <td style="padding: 3px 6px; text-align: right;">${{ number_format($sale->tax_amount, 2) }}</td>
                        </tr>
                    @endif
                    <tr style="font-weight: bold; font-size: 13px; border-top: 1px solid #000;">
                        <td style="padding: 5px 6px;">GRAND TOTAL:</td>
                        <td style="padding: 5px 6px; text-align: right; color: #0d6efd;">${{ number_format($sale->total_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 3px 6px;">Paid ({{ $sale->payment_method_label }}):</td>
                        <td style="padding: 3px 6px; text-align: right;">${{ number_format($sale->paid_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 3px 6px;">Change:</td>
                        <td style="padding: 3px 6px; text-align: right;">${{ number_format($sale->change_amount, 2) }}</td>
                    </tr>
                    @if ($sale->due_amount > 0)
                        <tr style="color: #dc2626; font-weight: bold;">
                            <td style="padding: 3px 6px;">Remaining Balance:</td>
                            <td style="padding: 3px 6px; text-align: right;">${{ number_format($sale->due_amount, 2) }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

@else
    <!-- ==================== 58mm / 80mm THERMAL PDF LAYOUT ==================== -->
    <div class="text-center">
        @if (!empty($logoBase64))
            <img src="{{ $logoBase64 }}" style="max-height: {{ $format === '58mm' ? '30px' : '40px' }}; margin-bottom: 4px;">
        @endif
        <div class="fw-bold" style="font-size: {{ $format === '58mm' ? '12px' : '15px' }};">{{ $store['store_name'] }}</div>
        <div>{{ $store['store_address'] }}</div>
        <div>Tel: {{ $store['store_phone'] }}</div>
    </div>

    @if ($isReprint)
        <div class="reprint-box">
            *** DUPLICATE (Reprint #{{ $sale->reprint_count }}) ***
        </div>
    @endif

    <div class="divider-dashed"></div>

    <table class="w-100">
        <tr>
            <td><strong>Inv:</strong> {{ $sale->invoice_no }}</td>
            <td class="text-right">{{ $sale->sale_date->format('Y-m-d H:i') }}</td>
        </tr>
        <tr>
            <td><strong>Cashier:</strong> {{ $sale->user?->name ?? 'Staff' }}</td>
            <td class="text-right"><strong>Cust:</strong> {{ $sale->customer?->name ?? 'Walk-in' }}</td>
        </tr>
    </table>

    <div class="divider-dashed"></div>

    <table class="thermal-table w-100">
        <thead>
            <tr style="border-bottom: 1px dashed #000;">
                <th class="text-left">Item</th>
                <th class="text-center" style="width: 15%;">Qty</th>
                <th class="text-right" style="width: 25%;">Price</th>
                <th class="text-right" style="width: 25%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td class="text-left">{{ $item->product?->name ?? 'Product' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right fw-bold">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider-dashed"></div>

    <table class="thermal-table w-100">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">${{ number_format($sale->subtotal, 2) }}</td>
        </tr>
        @if ($sale->discount_amount > 0)
            <tr>
                <td>Discount:</td>
                <td class="text-right">-${{ number_format($sale->discount_amount, 2) }}</td>
            </tr>
        @endif
        @if ($sale->tax_amount > 0)
            <tr>
                <td>Tax:</td>
                <td class="text-right">${{ number_format($sale->tax_amount, 2) }}</td>
            </tr>
        @endif
        <tr class="fw-bold" style="font-size: {{ $format === '58mm' ? '12px' : '14px' }}; border-top: 1px dashed #000; border-bottom: 1px dashed #000;">
            <td>TOTAL:</td>
            <td class="text-right">${{ number_format($sale->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Method:</td>
            <td class="text-right">{{ $sale->payment_method_label }}</td>
        </tr>
        <tr>
            <td>Paid:</td>
            <td class="text-right">${{ number_format($sale->paid_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Change:</td>
            <td class="text-right">${{ number_format($sale->change_amount, 2) }}</td>
        </tr>
        @if ($sale->due_amount > 0)
            <tr class="fw-bold">
                <td>Remaining Balance:</td>
                <td class="text-right">${{ number_format($sale->due_amount, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td>Status:</td>
            <td class="text-right fw-bold">{{ $sale->payment_status_label }}</td>
        </tr>
    </table>

    <div class="divider-dashed"></div>

    <div class="text-center" style="font-size: 9px; margin-top: 6px;">
        <div>{{ $store['receipt_footer'] }}</div>
        <div style="margin-top: 4px;">*** Thank you! ***</div>
    </div>
@endif

</body>
</html>
