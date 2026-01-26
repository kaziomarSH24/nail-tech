<!DOCTYPE html>
<html>
<head>
    <title>Income Report</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .text-right { text-align: right; }
        .header { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Income Report</h2>
        <p>Generated Date: {{ date('d M, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Transaction ID</th> <th>User</th>
                <th>Source</th>
                <th class="text-right">Discount</th> <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $txn)
                @php
                    $discount = 0;
                    if ($txn->payable) {
                        if ($txn->payable_type === 'App\Models\Order') {
                            $discount = $txn->payable->discount_amount ?? 0;
                        } elseif (in_array($txn->payable_type, ['App\Models\Course', 'App\Models\Enrollment'])) {
                            $discount = $txn->payable->discount_amount ?? 0;
                        }
                    }
                @endphp
            <tr>
                <td>{{ $txn->created_at->format('d M, Y') }}</td>
                <td>{{ $txn->gateway_transaction_id ?? $txn->id }}</td>

                <td>{{ $txn->user->name ?? 'Guest' }}</td>

                <td>{{ $txn->payable_type === 'App\Models\Order' ? 'Product' : 'Course' }}</td>

                <td class="text-right">
                    {{ $discount > 0 ? '$' . number_format($discount, 2) : '-' }}
                </td>

                <td class="text-right">${{ number_format($txn->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
