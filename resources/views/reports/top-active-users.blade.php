<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Active Users Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; color: #111827; background: #f8fafc; }
        .page { max-width: 960px; margin: 0 auto; padding: 32px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 24px; }
        .title { margin: 0; font-size: 28px; }
        .subtitle { margin: 6px 0 0; color: #6b7280; font-size: 14px; }
        .meta { color: #6b7280; font-size: 13px; text-align: right; }
        .actions { display: flex; gap: 10px; margin-bottom: 24px; }
        .button { display: inline-block; padding: 10px 14px; border-radius: 8px; text-decoration: none; border: 1px solid #d1d5db; color: #111827; background: #fff; font-size: 14px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; padding: 20px; }
        .card h2 { margin: 0 0 16px; font-size: 18px; }
        .row { margin-bottom: 16px; }
        .row-top { display: flex; justify-content: space-between; gap: 12px; font-size: 14px; margin-bottom: 8px; }
        .name { font-weight: 600; }
        .position { color: #6b7280; font-size: 12px; display: block; margin-top: 2px; }
        .track { height: 10px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
        .fill-blue { height: 10px; background: #2563eb; border-radius: 999px; }
        .fill-green { height: 10px; background: #16a34a; border-radius: 999px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e5e7eb; border-radius: 18px; overflow: hidden; }
        th, td { padding: 12px 14px; border-bottom: 1px solid #e5e7eb; text-align: left; font-size: 14px; }
        th { background: #f8fafc; font-weight: 700; }
        .section-title { margin: 0 0 12px; font-size: 18px; }
        @media print {
            body { background: #fff; }
            .page { max-width: none; padding: 16px; }
            .actions { display: none; }
        }
    </style>
    @if (! empty($autoPrint))
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</head>
<body>
    <div class="page">
        <div class="header">
            <div>
                <h1 class="title">Top Active Users Report</h1>
                <p class="subtitle">Graph view and detailed data for submissions and approvals.</p>
            </div>
            <div class="meta">
                <div>Generated: {{ $generatedAt->format('M d, Y h:i A') }}</div>
            </div>
        </div>

        @if (! $isExport)
            <div class="actions">
                <button class="button" onclick="window.print()">Print</button>
                <a class="button" href="{{ route('reports.top-active-users.pdf') }}">Open PDF View</a>
                <a class="button" href="{{ route('reports.top-active-users.excel') }}">Export Excel</a>
            </div>
        @endif

        @if (! empty($pdfMode))
            <p class="subtitle" style="margin-bottom: 18px;">Use your browser's destination `Save as PDF` in the print dialog.</p>
        @endif

        <div class="grid">
            <div class="card">
                <h2>Most Submissions</h2>
                @forelse ($topSubmitters as $user)
                    <div class="row">
                        <div class="row-top">
                            <div>
                                <span class="name">{{ $user['name'] }}</span>
                                <span class="position">{{ $user['position'] }}</span>
                            </div>
                            <strong>{{ number_format($user['total']) }}</strong>
                        </div>
                        <div class="track">
                            <div class="fill-blue" style="width: {{ $user['width'] }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p>No submission activity found.</p>
                @endforelse
            </div>

            <div class="card">
                <h2>Most Approvals</h2>
                @forelse ($topApprovers as $user)
                    <div class="row">
                        <div class="row-top">
                            <div>
                                <span class="name">{{ $user['name'] }}</span>
                                <span class="position">{{ $user['position'] }}</span>
                            </div>
                            <strong>{{ number_format($user['total']) }}</strong>
                        </div>
                        <div class="track">
                            <div class="fill-green" style="width: {{ $user['width'] }}%;"></div>
                        </div>
                    </div>
                @empty
                    <p>No approval activity found.</p>
                @endforelse
            </div>
        </div>

        <h2 class="section-title">Detailed Data</h2>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Name</th>
                    <th>Position</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($topSubmitters as $user)
                    <tr>
                        <td>Submission</td>
                        <td>{{ $user['name'] }}</td>
                        <td>{{ $user['position'] }}</td>
                        <td>{{ number_format($user['total']) }}</td>
                    </tr>
                @endforeach
                @foreach ($topApprovers as $user)
                    <tr>
                        <td>Approval</td>
                        <td>{{ $user['name'] }}</td>
                        <td>{{ $user['position'] }}</td>
                        <td>{{ number_format($user['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
