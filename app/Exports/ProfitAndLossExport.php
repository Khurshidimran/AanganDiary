<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProfitAndLossExport implements FromArray, WithHeadings
{
    /**
     * @param  Collection<int, array{account: \App\Models\Account, amount: float}>  $revenueAccounts
     * @param  Collection<int, array{account: \App\Models\Account, amount: float}>  $expenseAccounts
     */
    public function __construct(
        private readonly Collection $revenueAccounts,
        private readonly Collection $expenseAccounts,
        private readonly float $totalRevenue,
        private readonly float $totalExpense,
        private readonly float $netProfit,
    ) {
    }

    public function headings(): array
    {
        return ['Account', 'Amount'];
    }

    public function array(): array
    {
        $rows = [['Revenue', '']];

        foreach ($this->revenueAccounts as $row) {
            $rows[] = ["{$row['account']->code} — {$row['account']->name}", number_format($row['amount'], 2)];
        }

        $rows[] = ['Total Revenue', number_format($this->totalRevenue, 2)];
        $rows[] = ['', ''];
        $rows[] = ['Expenses', ''];

        foreach ($this->expenseAccounts as $row) {
            $rows[] = ["{$row['account']->code} — {$row['account']->name}", number_format($row['amount'], 2)];
        }

        $rows[] = ['Total Expenses', number_format($this->totalExpense, 2)];
        $rows[] = ['', ''];
        $rows[] = [$this->netProfit >= 0 ? 'Net Profit' : 'Net Loss', number_format(abs($this->netProfit), 2)];

        return $rows;
    }
}
