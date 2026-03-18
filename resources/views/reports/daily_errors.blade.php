<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
    <style>
        :root {
            --primary: #f87171;
            --secondary: #94a3b8;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border: #334155;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            margin: 0;
            padding: 40px;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .header {
            background: linear-gradient(135deg, #ef4444 0%, #7f1d1d 100%);
            padding: 32px;
            color: white;
            border-bottom: 2px solid var(--primary);
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        .header .meta {
            margin-top: 8px;
            opacity: 0.8;
            font-weight: 500;
            display: flex;
            justify-content: space-between;
        }
        .content {
            padding: 32px;
        }
        .summary-card {
            display: flex;
            align-items: center;
            background: rgba(127, 29, 29, 0.3);
            border: 1px solid rgba(248, 113, 113, 0.4);
            border-left: 4px solid var(--primary);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
        }
        .summary-card .count {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-right: 12px;
        }
        .summary-card .label {
            color: #fecaca;
            font-weight: 600;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 8px;
        }
        th {
            background: rgba(15, 23, 42, 0.5);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            padding: 12px 16px;
            text-align: left;
            border-bottom: 2px solid var(--border);
        }
        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
            color: var(--text-main);
        }
        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }
        tr:last-child td {
            border-bottom: none;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            background: #334155;
            color: #cbd5e1;
            border: 1px solid #475569;
        }
        .downtime {
            color: var(--primary);
            font-weight: 600;
        }
        .footer {
            padding: 24px 32px;
            background: rgba(15, 23, 42, 0.8);
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
        }
        .empty-state {
            padding: 48px;
            text-align: center;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $reportTitle }}</h1>
            <div class="meta">
                <span>{{ $date }}</span>
                <span>NOC Skynet Automated Systems</span>
            </div>
        </div>

        <div class="content">
            <div class="summary-card">
                <div class="count">{{ $affectedCustomers->count() }}</div>
                <div class="label">Customers Currently Experiencing Critical Downtime</div>
            </div>

            @if($affectedCustomers->isEmpty())
                <div class="empty-state">
                    <p style="font-size: 18px; font-weight: 500;">No critical issues detected at this time.</p>
                    <p>System status: Normal</p>
                </div>
            @else
                <table>
                    <thead>
                        <tr>
                            <th>Customer / IP</th>
                            <th>Area</th>
                            <th>Total Downtime</th>
                            <th>Last Check</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($affectedCustomers as $customer)
                            <tr>
                                <td>
                                    <div style="font-weight: 600;">{{ $customer->name }}</div>
                                    <div style="font-size: 12px; color: var(--text-muted);">{{ $customer->ip_address }}</div>
                                </td>
                                <td>
                                    <span class="badge">{{ $customer->area->name ?? 'Default' }}</span>
                                </td>
                                <td>
                                    @php
                                        $minutes = $customer->health_checks_count;
                                        $hours = floor($minutes / 60);
                                        $remainingMinutes = $minutes % 60;
                                        
                                        $durationString = "";
                                        if ($hours > 0) {
                                            $durationString .= $hours . 'h ';
                                        }
                                        $durationString .= $remainingMinutes . 'm';
                                    @endphp
                                    <span class="downtime">{{ $durationString }}</span>
                                </td>
                                <td style="font-variant-numeric: tabular-nums; font-size: 12px;">
                                     @if($customer->healthChecks->isNotEmpty())
                                        {{ \Carbon\Carbon::parse($customer->healthChecks->first()->checked_at)->format('H:i:s') }}
                                     @else
                                        -
                                     @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Skynet Customer Health Monitoring. All rights reserved.
        </div>
    </div>
</body>
</html>
