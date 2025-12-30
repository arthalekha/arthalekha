<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TransfersExport implements FromQuery, WithHeadings, WithMapping
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
            'From Account',
            'To Account',
            'Tags',
            'Amount',
        ];
    }

    /**
     * @param  \App\Models\Transfer  $transfer
     */
    public function map($transfer): array
    {
        return [
            $transfer->transacted_at->format('Y-m-d'),
            $transfer->description,
            $transfer->debtor->name,
            $transfer->creditor->name,
            $transfer->tags->pluck('name')->implode(', '),
            $transfer->amount,
        ];
    }
}
