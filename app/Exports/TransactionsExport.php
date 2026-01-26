<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransactionsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return ['Date', 'Transaction ID', 'User Name', 'Source', 'Amount', 'Status'];
    }

    public function map($txn): array
    {
        return [
            $txn->created_at->format('Y-m-d H:i:s'),
            $txn->gateway_transaction_id ?? $txn->id,
            $txn->user->name ?? 'Guest',
            $txn->payable_type === 'App\Models\Order' ? 'Product' : 'Course',
            $txn->amount,
            ucfirst($txn->status),
        ];
    }
}
