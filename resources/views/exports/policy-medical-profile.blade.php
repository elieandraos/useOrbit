<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $policy->policy_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 12px;
            color: #1a1a1a;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 4px;
        }

        .subtitle {
            color: #666666;
            margin: 0 0 24px;
        }

        h2 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #666666;
            border-bottom: 1px solid #dddddd;
            padding-bottom: 6px;
            margin: 20px 0 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 4px 0;
            vertical-align: top;
            width: 50%;
        }

        .label {
            display: block;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #999999;
        }

        .value {
            display: block;
            margin-top: 2px;
        }

        .members-table td {
            width: auto;
            border-bottom: 1px solid #eeeeee;
            padding: 6px 8px;
        }

        .members-table th {
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #999999;
            padding: 0 8px 6px;
        }
    </style>
</head>
<body>
    <h1>{{ $policy->policy_number }}</h1>
    <p class="subtitle">{{ $policy->class->label() }} policy profile</p>

    <h2>Policy details</h2>
    <table>
        <tr>
            <td>
                <span class="label">Client</span>
                <span class="value">{{ $policy->client->full_name }}</span>
            </td>
            <td>
                <span class="label">Carrier</span>
                <span class="value">{{ $policy->carrier->name }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Agent</span>
                <span class="value">{{ $policy->agent?->full_name ?? '—' }}</span>
            </td>
            <td>
                <span class="label">Type</span>
                <span class="value">{{ $policy->type->label() }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Effective date</span>
                <span class="value">{{ $policy->effective_date->format('M j, Y') }}</span>
            </td>
            <td>
                <span class="label">Expiry date</span>
                <span class="value">{{ $policy->expiry_date->format('M j, Y') }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Premium</span>
                <span class="value">{{ number_format((float) $policy->premium_amount, 2) }}</span>
            </td>
            <td>
                <span class="label">Discount</span>
                <span class="value">{{ number_format((float) $policy->discount_amount, 2) }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Status</span>
                <span class="value">{{ $policy->status->label() }}</span>
            </td>
            <td>
                <span class="label">Source</span>
                <span class="value">{{ $policy->source->label() }}</span>
            </td>
        </tr>
    </table>

    <h2>Medical details</h2>
    <table>
        <tr>
            <td>
                <span class="label">Coverage scope</span>
                <span class="value">{{ str($policy->medicalDetails->coverage_scope->value)->replace('_', ' ')->title() }}</span>
            </td>
            <td>
                <span class="label">Class tier</span>
                <span class="value">{{ str($policy->medicalDetails->class_tier->value)->replace('_', ' ')->title() }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Co-insurance</span>
                <span class="value">{{ $policy->medicalDetails->co_insurance ? "Yes ({$policy->medicalDetails->co_insurance_share}%)" : 'No' }}</span>
            </td>
            <td>
                <span class="label">Guaranteed renewable</span>
                <span class="value">{{ $policy->medicalDetails->guaranteed_renewable ? 'Yes' : 'No' }}</span>
            </td>
        </tr>
    </table>

    @if ($policy->type === \App\Enums\PolicyType::Single)
        <h2>Insured profile</h2>
        <table>
            <tr>
                <td>
                    <span class="label">Full name</span>
                    <span class="value">{{ $policy->medicalDetails->insured_full_name }}</span>
                </td>
                <td>
                    <span class="label">Gender</span>
                    <span class="value">{{ $policy->medicalDetails->insured_gender?->label() ?? '—' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Date of birth</span>
                    <span class="value">{{ $policy->medicalDetails->insured_date_of_birth?->format('M j, Y') ?? '—' }}</span>
                </td>
                <td>
                    <span class="label">Smoker</span>
                    <span class="value">{{ $policy->medicalDetails->insured_smoker ? 'Yes' : 'No' }}</span>
                </td>
            </tr>
        </table>
    @else
        <h2>Covered members</h2>
        @if ($policy->insureds->isNotEmpty())
            <table class="members-table">
                <tr>
                    <th>Member</th>
                    <th>Relationship</th>
                    <th>Date of birth</th>
                    <th>Gender</th>
                </tr>
                @foreach ($policy->insureds as $insured)
                    <tr>
                        <td>{{ $insured->full_name }}</td>
                        <td>{{ $insured->relationship }}</td>
                        <td>{{ $insured->date_of_birth->format('M j, Y') }}</td>
                        <td>{{ $insured->gender?->label() ?? '—' }}</td>
                    </tr>
                @endforeach
            </table>
        @else
            <p>No covered members on file.</p>
        @endif
    @endif
</body>
</html>
