<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Error Report</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .header {
            margin-bottom: 20px;
        }
        .date {
            color: #666;
            font-size: 14px;
        }
        .summary {
            margin-bottom: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Skynet Customer Health - Daily Error Report</h1>
        <div class="date">For Date: {{ $date }}</div>
    </div>

    <div class="summary">
        Total Affected Customers: {{ $affectedCustomers->count() }}
    </div>

    @if($affectedCustomers->isEmpty())
        <p>No errors recorded for this date.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Area</th>
                    <th>IP Address</th>
                    <th>Total Downtime</th>
                    <th>Last Checked At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($affectedCustomers as $customer)
                    <tr>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->area->name ?? 'N/A' }}</td>
                        <td>{{ $customer->ip_address }}</td>
                        {{-- checks run every minute, so count = minutes --}}
                        <td>
                            @php
                                $minutes = $customer->health_checks_count;
                                $hours = floor($minutes / 60);
                                $remainingMinutes = $minutes % 60;
                                $durationString = "";
                                if ($hours > 0) {
                                    $durationString .= $hours . "h ";
                                }
                                if ($remainingMinutes > 0 || $hours == 0) {
                                    $durationString .= $remainingMinutes . "m";
                                }
                            @endphp
                            {{ $durationString }}
                        </td>
                        <td>
                             @if($customer->healthChecks->isNotEmpty())
                                {{ $customer->healthChecks->first()->checked_at }}
                             @else
                                N/A
                             @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
