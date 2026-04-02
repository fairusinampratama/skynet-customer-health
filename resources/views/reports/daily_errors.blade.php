<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $reportTitle }}</title>
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
        <h1 style="color: #d32f2f; margin-bottom: 5px;">{{ $reportTitle }}</h1>
        <div class="date">Date: {{ $date }}</div>
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
                    <th>No.</th>
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
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->area->name ?? 'N/A' }}</td>
                        <td>{{ $customer->ip_address }}</td>
                        {{-- Downtime calculated from updated_at (when status changed to 'down') --}}
                        <td>{{ $customer->updated_at->diffForHumans(null, true) }}</td>
                        <td>{{ $customer->updated_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
