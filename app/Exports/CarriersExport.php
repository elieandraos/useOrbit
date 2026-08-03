<?php

declare(strict_types=1);

namespace App\Exports;

use App\Filters\CarrierFilter;
use App\Models\Carrier;
use App\Sorts\CarrierSort;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final readonly class CarriersExport implements FromQuery, WithHeadings, WithMapping
{
    /** @param  array<string, mixed>  $filters */
    public function __construct(
        private array $filters,
        private ?string $sortColumn,
        private string $sortDirection,
    ) {}

    public function query(): Builder
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return Carrier::query()
            ->with('branches')
            ->filter(new CarrierFilter($this->filters))
            ->sort(new CarrierSort($this->sortColumn, $this->sortDirection));
    }

    public function headings(): array
    {
        return [
            'Name',
            'Phone',
            'Website',
            'Branches',
            'Clients',
            'Policies',
            'Status',
        ];
    }

    public function map($row): array
    {
        /** @var Carrier $row */
        return [
            $row->name,
            $row->phone,
            $row->website,
            $row->branches->count(),
            0,
            0,
            ucfirst($row->status->value),
        ];
    }
}
