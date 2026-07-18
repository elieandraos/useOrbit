<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Documents\FinalizeDocumentsUploadBatchAction;
use App\Http\Requests\Documents\DocumentsUploadBatchRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class DocumentsUploadBatchController extends Controller
{
    /**
     * @throws \Throwable
     */
    public function __invoke(DocumentsUploadBatchRequest $request, FinalizeDocumentsUploadBatchAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $request->validated('document_ids'));

        return back();
    }
}
