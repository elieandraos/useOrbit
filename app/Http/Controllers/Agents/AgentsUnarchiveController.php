<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agents;

use App\Actions\Agents\UnarchiveAgentAction;
use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Inertia;

final class AgentsUnarchiveController extends Controller
{
    /**
     * @throws \Throwable
     */
    #[Authorize('unarchive', 'agent')]
    public function __invoke(Request $request, Agent $agent, UnarchiveAgentAction $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $action->handle($user, $agent);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Agent unarchived.')]);

        return to_route('agents.index');
    }
}
