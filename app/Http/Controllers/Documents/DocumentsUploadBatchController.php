<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documents;

use App\Actions\Documents\FinalizeDocumentsUploadBatchAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\DocumentsUploadBatchRequest;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class DocumentsUploadBatchController extends Controller
{
    /**
     * @throws \Throwable
     */
    #[Authorize('finalize', Document::class)]
    public function __invoke(DocumentsUploadBatchRequest $request, FinalizeDocumentsUploadBatchAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $rejectedCount = $action->handle($user, $request->validated('document_ids'));

        if ($rejectedCount > 0) {
            Inertia::flash('toast', ['type' => 'warning', 'message' => __('Some files could not be submitted for processing.')]);
        }

        return back();
    }
}
