<?php

declare(strict_types=1);

namespace App\Http\Controllers\Clients;

use App\Actions\Clients\ExportClientToPdfAction;
use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Authorize;

final class ClientsPdfExportController extends Controller
{
    #[Authorize('view', 'client')]
    public function __invoke(Client $client, ExportClientToPdfAction $action): Response
    {
        $client->load('country');

        return $action->handle($client);
    }
}
