<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class ExportClientToPdfAction
{
    public function handle(Client $client): Response
    {
        return Pdf::loadView('exports.client-profile', ['client' => $client])
            ->download("$client->slug.pdf");
    }
}
