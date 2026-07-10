<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $client->first_name }} {{ $client->last_name }}</title>
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

        .value-empty {
            color: #999999;
        }
    </style>
</head>
<body>
    <h1>{{ $client->first_name }} {{ $client->last_name }}</h1>
    <p class="subtitle">Client profile</p>

    <h2>Personal information</h2>
    <table>
        <tr>
            <td>
                <span class="label">First name</span>
                <span class="value">{{ $client->first_name }}</span>
            </td>
            <td>
                <span class="label">Middle name</span>
                <span class="value">{{ $client->middle_name ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Last name</span>
                <span class="value">{{ $client->last_name }}</span>
            </td>
            <td>
                <span class="label">Mother's name</span>
                <span class="value">{{ $client->mothers_name ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Date of birth</span>
                <span class="value">{{ $client->date_of_birth->format('M j, Y') }} ({{ $client->date_of_birth->age }} yrs)</span>
            </td>
            <td>
                <span class="label">Gender</span>
                <span class="value">{{ $client->gender->label() }}</span>
            </td>
        </tr>
    </table>

    <h2>Contact</h2>
    <table>
        <tr>
            <td>
                <span class="label">Phone</span>
                <span class="value">{{ $client->phone }}</span>
            </td>
            <td>
                <span class="label">Email</span>
                <span class="value">{{ $client->email ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="label">Address</span>
                <span class="value">
                    {!! nl2br(e(collect([$client->street, $client->building_floor, $client->city, $client->state, $client->country?->name])->filter()->implode("\n"))) ?: '—' !!}
                </span>
            </td>
        </tr>
    </table>

    <h2>Enrollment</h2>
    <table>
        <tr>
            <td>
                <span class="label">Enrolled</span>
                <span class="value">{{ $client->enrollment_date->format('M j, Y') }}</span>
            </td>
            <td>
                <span class="label">Lead source</span>
                <span class="value">{{ $client->lead_source->label() }}</span>
            </td>
        </tr>
    </table>

    <h2>Emergency contact</h2>
    @if ($client->emergency_contact_name)
        <table>
            <tr>
                <td>
                    <span class="label">Name</span>
                    <span class="value">{{ $client->emergency_contact_name }}</span>
                </td>
                <td>
                    <span class="label">Relationship</span>
                    <span class="value">{{ $client->emergency_contact_relationship?->label() ?? '—' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">Phone</span>
                    <span class="value">{{ $client->emergency_contact_phone ?? '—' }}</span>
                </td>
            </tr>
        </table>
    @else
        <p class="value-empty">No emergency contact on file.</p>
    @endif
</body>
</html>
