<?php

declare(strict_types=1);

namespace App\Http\Controllers\Policies;

use App\Enums\PolicyClass;
use App\Enums\PolicyType;
use App\Http\Controllers\Controller;
use App\Http\Resources\PolicyInsuredResource;
use App\Http\Resources\PolicyMedicalResource;
use App\Models\Policy;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Inertia\Response;

final class PolicyMembersController extends Controller
{
    #[Authorize('view', 'policy')]
    public function index(Policy $policy): Response
    {
        abort_unless($policy->class === PolicyClass::Medical, 404);
        abort_unless($policy->type === PolicyType::Group, 404);

        $policy->load(['client', 'carrier', 'agent']);

        $members = $policy->insureds()->orderBy('full_name')->get();

        return inertia('PolicyMembers/Index', [
            'policy' => PolicyMedicalResource::make($policy),
            'members' => PolicyInsuredResource::collection($members),
        ]);
    }
}
