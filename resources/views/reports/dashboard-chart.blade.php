<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; color: #111827; background: #f8fafc; }
        .page { max-width: 900px; margin: 0 auto; padding: 28px; }
        .header { display: flex; justify-content: space-between; gap: 16px; margin-bottom: 24px; }
        .title { margin: 0; font-size: 26px; }
        .subtitle { margin: 6px 0 0; color: #6b7280; font-size: 14px; }
        .meta { color: #6b7280; font-size: 13px; text-align: right; }
        .actions { display: flex; gap: 10px; margin-bottom: 18px; }
        .button { display: inline-block; padding: 10px 14px; border-radius: 8px; text-decoration: none; border: 1px solid #d1d5db; color: #111827; background: #fff; font-size: 14px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 20px; margin-bottom: 24px; }
        .row { margin-bottom: 16px; }
        .row-top { display: flex; justify-content: space-between; gap: 12px; font-size: 14px; margin-bottom: 8px; }
        .track { height: 12px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
        .fill { height: 12px; background: #2563eb; border-radius: 999px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; overflow: hidden; }
        th, td { padding: 12px 14px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 14px; }
        th { background: #f8fafc; font-weight: 700; }
        @media print { .actions { display: none; } body { background: #fff; } .page { max-width: none; padding: 16px; } }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div>
            <h1 class="title">{{ $title }}</h1>
            <p class="subtitle">{{ $subtitle }}</p>
        </div>
        <div class="meta">
            <div>Generated: {{ $generatedAt->format('M d, Y h:i A') }}</div>
            <div>Total: {{ $total }}</div>
        </div>
    </div>

    @if (! $isExport)
        <div class="actions">
            <button class="button" onclick="window.print()">Print</button>
            <a class="button" href="{{ route('reports.dashboard.pdf', ['report' => $report, 'filter' => $filter ?? null]) }}">Export PDF</a>
            <a class="button" href="{{ route('reports.dashboard.excel', ['report' => $report, 'filter' => $filter ?? null]) }}">Export Excel</a>
        </div>
    @endif

    <div class="card">
        @foreach ($rows as $row)
            <div class="row">
                <div class="row-top">
                    <strong>{{ $row['label'] }}</strong>
                    <span>{{ $row['formatted_value'] }}</span>
                </div>
                <div class="track">
                    <div class="fill" style="width: {{ $row['width'] }}%;"></div>
                </div>
            </div>
        @endforeach
    </div>

    <table>
        <thead>
        <tr>
            <th>Label</th>
            <th>{{ $metricLabel }}</th>
            @if (! empty($percentage))
                <th>Raw Total</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>{{ $row['formatted_value'] }}</td>
                @if (! empty($percentage))
                    <td>{{ number_format((int) ($row['raw_total'] ?? 0)) }}</td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
