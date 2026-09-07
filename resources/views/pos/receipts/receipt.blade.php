<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $sale->invoice_no }} ({{ strtoupper($format) }})</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f1f5f9;
            color: #1e293b;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        /* Top Action Bar */
        .receipt-toolbar {
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* Paper sheet wrapper */
        .paper-sheet {
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin: 25px auto;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        /* 58mm Styling */
        .format-58mm .paper-sheet {
            max-width: 220px;
            padding: 12px 10px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.35;
        }
        .store-logo-58 {
            max-width: 110px;
            max-height: 40px;
            object-fit: contain;
            margin-bottom: 4px;
        }
        .receipt-58mm .store-name { font-size: 13px; margin-bottom: 2px; }
        .receipt-58mm .store-info { font-size: 10px; color: #475569; }
        .receipt-58mm .item-name { font-size: 11px; word-break: break-word; }
        .receipt-58mm .item-details { display: flex; justify-content: space-between; font-size: 10.5px; }
        .receipt-58mm .grand-total-row { font-size: 13px; margin: 4px 0; }
        .receipt-58mm .status-tag {
            font-weight: bold;
            font-size: 10px;
            padding: 1px 4px;
            border: 1px solid #000;
            border-radius: 2px;
            text-transform: uppercase;
        }

        /* 80mm Styling */
        .format-80mm .paper-sheet {
            max-width: 320px;
            padding: 18px 16px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
        }
        .store-logo-80 {
            max-width: 150px;
            max-height: 50px;
            object-fit: contain;
            margin-bottom: 6px;
        }
        .receipt-80mm .store-name { font-size: 16px; letter-spacing: 0.5px; }
        .receipt-80mm .store-info { font-size: 11px; color: #475569; }
        .receipt-80mm .items-table th, .receipt-80mm .items-table td { padding: 3px 0; font-size: 12px; }
        .receipt-80mm .totals-table td { padding: 2px 0; }
        .receipt-80mm .grand-total-row td { padding: 5px 0; font-size: 15px; border-top: 1px dashed #000; border-bottom: 1px dashed #000; }
        .receipt-80mm .status-badge {
            display: inline-block;
            padding: 2px 6px;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #000;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .receipt-80mm .barcode-box {
            padding: 4px;
            display: inline-block;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            margin-top: 8px;
        }

        /* A4 Styling */
        .format-a4 .paper-sheet {
            max-width: 820px;
            min-height: 1050px;
            padding: 40px;
            font-size: 13px;
        }
        .a4-logo {
            max-width: 180px;
            max-height: 60px;
            object-fit: contain;
        }
        .a4-table th, .a4-table td { padding: 10px; }

        /* Shared dividers */
        .divider-dashed { border-bottom: 1px dashed #000000; margin: 8px 0; }
        .divider-solid { border-bottom: 1px solid #000000; margin: 6px 0; }
        .reprint-badge {
            font-weight: bold;
            font-size: 11px;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 3px 6px;
            margin: 6px 0;
            border-radius: 4px;
        }

        /* Print Media Styles */
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; padding: 0 !important; }
            .paper-sheet {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                border-radius: 0 !important;
            }
            @page {
                margin: 0;
            }
        }
    </style>
</head>
<body class="format-{{ $format }}">

    <!-- Action Toolbar (Hidden during printing) -->
    <div class="receipt-toolbar no-print p-3">
        <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center gap-3">
            <!-- Format Switcher Buttons -->
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small fw-bold text-uppercase me-1">
                    <i class="bi bi-aspect-ratio me-1"></i> Format:
                </span>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => '58mm']) }}"
                       class="btn {{ $format === '58mm' ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}">
                        58mm Thermal
                    </a>
                    <a href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => '80mm']) }}"
                       class="btn {{ $format === '80mm' ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}">
                        80mm Thermal
                    </a>
                    <a href="{{ route('pos.receipt.show', ['sale' => $sale, 'format' => 'a4']) }}"
                       class="btn {{ $format === 'a4' ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}">
                        A4 Invoice
                    </a>
                </div>
            </div>

            <!-- Action Controls: Print, Download PDF, Reprint, Close -->
            <div class="d-flex align-items-center gap-2">
                <!-- Print Button -->
                <button type="button" class="btn btn-sm btn-primary px-3 fw-bold shadow-xs" onclick="window.print()">
                    <i class="bi bi-printer-fill me-1"></i> Print Receipt
                </button>

                <!-- Download PDF Button -->
                <a href="{{ route('pos.receipt.pdf', ['sale' => $sale, 'format' => $format]) }}" class="btn btn-sm btn-outline-danger px-3 fw-semibold">
                    <i class="bi bi-file-earmark-pdf-fill me-1"></i> Download PDF
                </a>

                <!-- Reprint Receipt Button (Logs duplicate) -->
                <form action="{{ route('pos.receipt.reprint', ['sale' => $sale]) }}" method="POST" class="d-inline" onsubmit="return confirm('Log duplicate receipt reprint for #{{ $sale->invoice_no }}?')">
                    @csrf
                    <input type="hidden" name="format" value="{{ $format }}">
                    <button type="submit" class="btn btn-sm btn-outline-warning text-dark px-3 fw-semibold" title="Record duplicate ticket and increment reprint counter">
                        <i class="bi bi-arrow-repeat me-1"></i> Reprint Receipt
                    </button>
                </form>

                <!-- Close Window / Back -->
                <button type="button" class="btn btn-sm btn-light border px-3" onclick="window.close()">
                    <i class="bi bi-x-lg me-1"></i> Close
                </button>
            </div>
        </div>
    </div>

    <!-- Paper Sheet Container -->
    <div class="paper-sheet">
        @if ($format === '58mm')
            @include('pos.receipts.58mm')
        @elseif ($format === 'a4')
            @include('pos.receipts.a4')
        @else
            @include('pos.receipts.80mm')
        @endif
    </div>

    <!-- Auto-print script if requested -->
    @if (request()->boolean('autoprint'))
        <script>
            window.addEventListener('load', function() {
                window.print();
            });
        </script>
    @endif
</body>
</html>
