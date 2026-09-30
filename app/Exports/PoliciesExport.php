<?php

declare(strict_types=1);

namespace App\Exports;

use App\Filters\PolicyFilter;
use App\Models\Policy;
use App\Sorts\PolicySort;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final readonly class PoliciesExport implements FromQuery, WithHeadings, WithMapping
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
        return Policy::query()
            ->with(['client', 'carrier', 'agent'])
            ->filter(new PolicyFilter($this->filters))
            ->sort(new PolicySort($this->sortColumn, $this->sortDirection));
    }

    public function headings(): array
    {
        return [
            'Policy Number',
            'Class',
            'Type',
            'Client',
            'Carrier',
            'Agent',
            'Effective Date',
            'Expiry Date',
            'Premium Amount',
            'Discount Amount',
            'Status',
            'Source',
        ];
    }

    public function map($row): array
    {
        /** @var Policy $row */
        return [
            $row->policy_number,
            $row->class->label(),
            $row->type->label(),
            $row->client->full_name,
            $row->carrier->name,
            $row->agent?->full_name,
            $row->effective_date->format('Y-m-d'),
            $row->expiry_date->format('Y-m-d'),
            $row->premium_amount,
            $row->discount_amount,
            $row->status->label(),
            $row->source->label(),
        ];
    }
}
