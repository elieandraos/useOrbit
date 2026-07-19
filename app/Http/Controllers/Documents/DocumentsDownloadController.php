<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documents;

use App\Actions\Documents\DownloadDocumentAction;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Symfony\Component\HttpFoundation\Response;

final class DocumentsDownloadController extends Controller
{
    #[Authorize('view', 'document')]
    public function __invoke(Document $document, DownloadDocumentAction $action): Response
    {
        return $action->handle($document);
    }
}
