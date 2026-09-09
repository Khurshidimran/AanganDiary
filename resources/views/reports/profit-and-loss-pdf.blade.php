<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Profit & Loss</title>
    <style>
        @page { margin: 14mm; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #000; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        .subtitle { font-size: 10px; color: #555; margin: 0 0 14px; }
        h2 { font-size: 12px; margin: 16px 0 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .text-end { text-align: right; }
        tfoot td { font-weight: bold; background-color: #f7f7f7; }
        .net td { border: 1px solid #ccc; padding: 8px 10px; font-size: 13px; font-weight: bold; background-color: #f7f7f7; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} — Profit &amp; Loss</h1>
    <p class="subtitle">{{ $dateFrom->format('M j, Y') }} to {{ $dateTo->format('M j, Y') }} — Generated {{ now()->format('Y-m-d h:i A') }}</p>

    <h2>Revenue</h2>
    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($revenueAccounts as $row)
                <tr>
                    <td>{{ $row['account']->code }} — {{ $row['account']->name }}</td>
                    <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No revenue in this period.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td>Total Revenue</td>
                <td class="text-end">{{ number_format($totalRevenue, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <h2>Expenses</h2>
    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th class="text-end">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($expenseAccounts as $row)
                <tr>
                    <td>{{ $row['account']->code }} — {{ $row['account']->name }}</td>
                    <td class="text-end">{{ number_format($row['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="2">No expenses in this period.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td>Total Expenses</td>
                <td class="text-end">{{ number_format($totalExpense, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="net" style="margin-top: 18px;">
        <tr>
            <td>Net {{ $netProfit >= 0 ? 'Profit' : 'Loss' }}</td>
            <td class="text-end">{{ number_format(abs($netProfit), 2) }}</td>
        </tr>
    </table>
</body>
</html>
