<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #1e3a8a; color: #ffffff; font-weight: bold; border: 1px solid #94a3b8; padding: 8px; font-size: 11pt; }
        td { border: 1px solid #cbd5e1; padding: 6px; font-size: 10pt; }
        .title { font-size: 16pt; font-weight: bold; color: #1e3a8a; }
        .meta { font-size: 10pt; color: #475569; }
        .kpi-header { background-color: #f1f5f9; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="{{ count($headers) }}" class="title">{{ $title }}</td>
        </tr>
        <tr>
            <td colspan="{{ count($headers) }}" class="meta">Store: {{ config('app.name', 'POS Management') }} | Period: {{ $dateRange['label'] ?? 'All Dates' }} | Exported: {{ now()->format('Y-m-d H:i:s') }}</td>
        </tr>
        <tr><td colspan="{{ count($headers) }}"></td></tr>

        @if(!empty($kpis))
            <tr>
                @foreach($kpis as $kpi)
                    <td class="kpi-header">{{ $kpi['label'] }}</td>
                @endforeach
            </tr>
            <tr>
                @foreach($kpis as $kpi)
                    <td><strong>{{ $kpi['value'] }}</strong></td>
                @endforeach
            </tr>
            <tr><td colspan="{{ count($headers) }}"></td></tr>
        @endif

        <tr>
            @foreach($headers as $h)
                <th class="{{ $h['class'] ?? '' }}">{{ $h['label'] }}</th>
            @endforeach
        </tr>

        @forelse($rows as $row)
            <tr>
                @foreach($row as $idx => $cell)
                    <td class="{{ $headers[$idx]['class'] ?? '' }}">{!! strip_tags($cell) !!}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ count($headers) }}" style="text-align: center; color: #64748b;">No records found.</td>
            </tr>
        @endforelse
    </table>
</body>
</html>
