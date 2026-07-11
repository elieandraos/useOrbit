<?php

declare(strict_types=1);

namespace App\Exports;

use App\Filters\ClientFilter;
use App\Models\Client;
use App\Sorts\ClientSort;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final readonly class ClientsExport implements FromQuery, WithHeadings, WithMapping
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
        return Client::query()
            ->with('country')
            ->filter(new ClientFilter($this->filters))
            ->sort(new ClientSort($this->sortColumn, $this->sortDirection));
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone',
            'Gender',
            'Date of Birth',
            'Age',
            'Address',
            'Enrollment Date',
            'Lead Source',
            'Status',
        ];
    }

    public function map($row): array
    {
        /** @var Client $row */
        return [
            "$row->first_name $row->last_name",
            $row->email,
            $row->phone,
            $row->gender->label(),
            $row->date_of_birth->format('Y-m-d'),
            $row->date_of_birth->age,
            collect([$row->street, $row->building_floor, $row->city, $row->state, $row->country?->name])->filter()->implode(', '),
            $row->enrollment_date->format('Y-m-d'),
            $row->lead_source->label(),
            ucfirst($row->status->value),
        ];
    }
}
