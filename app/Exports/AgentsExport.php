<?php

declare(strict_types=1);

namespace App\Exports;

use App\Filters\AgentFilter;
use App\Models\Agent;
use App\Sorts\AgentSort;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final readonly class AgentsExport implements FromQuery, WithHeadings, WithMapping
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
        return Agent::query()
            ->filter(new AgentFilter($this->filters))
            ->sort(new AgentSort($this->sortColumn, $this->sortDirection));
    }

    public function headings(): array
    {
        return [
            'Name',
            'Phone',
            'Email',
            'Joined',
            'Clients',
            'Policies',
            'Status',
        ];
    }

    public function map($row): array
    {
        /** @var Agent $row */
        return [
            "$row->first_name $row->last_name",
            $row->phone,
            $row->email,
            $row->joined_at->format('M j, Y'),
            0,
            0,
            ucfirst($row->status->value),
        ];
    }
}
