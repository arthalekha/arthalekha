<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ExpensesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected Builder $query) {}

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Description',
            'Account',
            'Person',
            'Tags',
            'Amount',
        ];
    }

    /**
     * @param  \App\Models\Expense  $expense
     */
    public function map($expense): array
    {
        return [
            $expense->transacted_at->format('Y-m-d'),
            $expense->description,
            $expense->account->name,
            $expense->person?->name ?? '',
            $expense->tags->pluck('name')->implode(', '),
            $expense->amount,
        ];
    }
}
