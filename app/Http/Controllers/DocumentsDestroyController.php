<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Documents\DeleteDocumentAction;
use App\Models\Document;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class DocumentsDestroyController extends Controller
{
    #[Authorize('delete', 'document')]
    public function __invoke(Document $document, DeleteDocumentAction $action): RedirectResponse
    {
        $action->handle($document);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Document deleted.')]);

        return back();
    }
}
