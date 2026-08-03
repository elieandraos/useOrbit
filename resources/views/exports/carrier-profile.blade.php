<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $carrier->name }}</title>
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

        .branch {
            margin-bottom: 16px;
        }

        .branch:last-child {
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <h1>{{ $carrier->name }}</h1>
    <p class="subtitle">Carrier profile</p>

    <h2>Carrier information</h2>
    <table>
        <tr>
            <td>
                <span class="label">Phone</span>
                <span class="value">{{ $carrier->phone ?? '—' }}</span>
            </td>
            <td>
                <span class="label">Website</span>
                <span class="value">{{ $carrier->website ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">Status</span>
                <span class="value">{{ ucfirst($carrier->status->value) }}</span>
            </td>
            <td>
                <span class="label">Branches</span>
                <span class="value">{{ $carrier->branches->count() }}</span>
            </td>
        </tr>
    </table>

    <h2>Branches</h2>
    @if ($carrier->branches->isEmpty())
        <p class="value-empty">No branch on file.</p>
    @else
        @foreach ($carrier->branches as $branch)
            <div class="branch">
                <table>
                    <tr>
                        <td colspan="2">
                            <span class="label">Address</span>
                            <span class="value">
                                {!! nl2br(e(collect([$branch->building_floor, $branch->street, $branch->city, $branch->state?->name, $branch->country?->name])->filter()->implode("\n"))) ?: '—' !!}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span class="label">Contact person</span>
                            <span class="value">{{ $branch->contact_name }}{{ $branch->contact_role ? " · $branch->contact_role" : '' }}</span>
                        </td>
                        <td>
                            <span class="label">Contact details</span>
                            <span class="value">{{ collect([$branch->contact_email, $branch->contact_phone])->filter()->implode(' · ') ?: '—' }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach
    @endif
</body>
</html>
