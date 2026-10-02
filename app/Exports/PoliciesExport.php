<?php

declare(strict_types=1);

namespace App\Exports;

use App\Filters\PolicyFilter;
use App\Models\Policy;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

final readonly class PoliciesExport implements FromQuery, WithColumnFormatting, WithHeadings, WithMapping
{
    /** @param  array<string, mixed>  $filters */
    public function __construct(private array $filters) {}

    public function query(): Builder
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return Policy::query()
            ->with(['client', 'carrier', 'agent', 'currency'])
            ->filter(new PolicyFilter($this->filters))
            ->inListOrder();
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
            'Currency',
            'Premium Amount',
            'Discount Amount',
            'Status',
            'Source',
        ];
    }

    /**
     * Keep the premium and discount cells numeric, shown with two decimals and thousands separators.
     *
     * @return array<string, string>
     */
    public function columnFormats(): array
    {
        return [
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'K' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
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
            $row->currency->code,
            (float) $row->premium_amount,
            (float) $row->discount_amount,
            $row->status->label(),
            $row->source->label(),
        ];
    }
}
