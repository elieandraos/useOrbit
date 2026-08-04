<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agents;

use App\Actions\Agents\ArchiveAgentAction;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class AgentsArchiveController extends Controller
{
    /**
     * @throws \Throwable
     */
    #[Authorize('archive', 'agent')]
    public function __invoke(Request $request, Agent $agent, ArchiveAgentAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $agent);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agent archived.')]);

        return to_route('agents.index');
    }
}
