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

    <h2>Expat details</h2>
    <table>
        <tr>
            <td>
                <span class="label">Full name</span>
                <span class="value">{{ $policy->expatDetails->full_name }}</span>
            </td>
            <td>
                <span class="label">Gender</span>
                <span class="value">{{ $policy->expatDetails->gender->label() }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Nationality</span>
                <span class="value">{{ $policy->expatDetails->nationality }}</span>
            </td>
            <td>
                <span class="label">Date of birth</span>
                <span class="value">{{ $policy->expatDetails->date_of_birth->format('M j, Y') }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Phone</span>
                <span class="value">{{ $policy->expatDetails->phone }}</span>
            </td>
            <td>
                <span class="label">Country of residence</span>
                <span class="value">{{ $policy->expatDetails->country?->name ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Coverage zone</span>
                <span class="value">{{ $policy->expatDetails->coverage_zone->label() }}</span>
            </td>
            <td>
                <span class="label">Travel scope</span>
                <span class="value">{{ $policy->expatDetails->travel_scope ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Visa/residency expiry</span>
                <span class="value">{{ $policy->expatDetails->visa_expiry_date?->format('M j, Y') ?? '—' }}</span>
            </td>
        </tr>
    </table>
</body>
</html>
