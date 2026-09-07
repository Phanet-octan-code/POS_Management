<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'Report' }}</title>
    <style>
        @page {
            margin: 10mm;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
            background: #fff;
            margin: 0;
            padding: 0;
            line-height: 1.35;
        }
        .header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header table {
            width: 100%;
        }
        .store-title {
            font-size: 16px;
            font-weight: bold;
            color: #0d6efd;
        }
        .report-title {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: right;
        }
        .kpi-row {
            width: 100%;
            margin-bottom: 12px;
        }
        .kpi-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 6px 10px;
            border-radius: 4px;
            text-align: center;
        }
        .kpi-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
        }
        .kpi-val {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #334155;
            font-size: 9px;
            text-transform: uppercase;
            padding: 5px 6px;
            border: 1px solid #cbd5e1;
            text-align: left;
        }
        table.data-table td {
            padding: 4px 6px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }
        .fw-bold { font-weight: bold; }
        .footer {
            margin-top: 15px;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            font-size: 8px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <table>
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div class="store-title">{{ $store_name }}</div>
                <div style="color: #475569;">{{ $store_address }}</div>
                <div style="color: #475569;">Tel: {{ $store_phone }}</div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right;">
                <div class="report-title">{{ $title }}</div>
                <div style="color: #475569; margin-top: 3px;"><strong>Period:</strong> {{ $dateRange['label'] ?? 'All Dates' }}</div>
                <div style="color: #64748b; font-size: 8px;">Generated on: {{ $printed_at }}</div>
            </td>
        </tr>
    </table>
</div>

<!-- KPIs (if provided) -->
@if(!empty($kpis))
    <table class="kpi-row">
        <tr>
            @foreach($kpis as $kpi)
                <td style="padding: 0 4px; vertical-align: top;">
                    <div class="kpi-box">
                        <div class="kpi-label">{{ $kpi['label'] }}</div>
                        <div class="kpi-val" style="{{ $kpi['style'] ?? '' }}">{{ $kpi['value'] }}</div>
                    </div>
                </td>
            @endforeach
        </tr>
    </table>
@endif

<!-- Data Table -->
<table class="data-table">
    <thead>
        <tr>
            @foreach($headers as $h)
                <th class="{{ $h['class'] ?? '' }}">{{ $h['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $row)
            <tr>
                @foreach($row as $idx => $cell)
                    <td class="{{ $headers[$idx]['class'] ?? '' }}">{!! $cell !!}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($headers) }}" class="text-center" style="padding: 15px; color: #64748b;">
                    No records found for this period.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<!-- Footer -->
<div class="footer">
    <span>POS Management System &bull; Confidential Business Financial Record &bull; Page printed automatically</span>
</div>

</body>
</html>
