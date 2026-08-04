<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $agent->first_name }} {{ $agent->last_name }}</title>
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
    <h1>{{ $agent->first_name }} {{ $agent->last_name }}</h1>
    <p class="subtitle">Agent profile</p>

    <h2>Personal information</h2>
    <table>
        <tr>
            <td>
                <span class="label">Date of birth</span>
                <span class="value">{{ $agent->date_of_birth->format('M j, Y') }}</span>
            </td>
            <td>
                <span class="label">Status</span>
                <span class="value">{{ ucfirst($agent->status->value) }}</span>
            </td>
        </tr>
    </table>

    <h2>Contact</h2>
    <table>
        <tr>
            <td>
                <span class="label">Phone</span>
                <span class="value">{{ $agent->phone }}</span>
            </td>
            <td>
                <span class="label">Email</span>
                <span class="value">{{ $agent->email }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="label">Address</span>
                <span class="value">
                    {!! nl2br(e(collect([$agent->building_floor, $agent->street, $agent->city, $agent->state?->name, $agent->country?->name])->filter()->implode("\n"))) ?: '—' !!}
                </span>
            </td>
        </tr>
    </table>
</body>
</html>
