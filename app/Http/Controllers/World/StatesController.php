<?php

declare(strict_types=1);

namespace App\Http\Controllers\World;

use App\Http\Controllers\Controller;
use App\Http\Controllers\World\Concerns\RanksSearchResults;
use App\Http\Requests\World\SearchStatesRequest;
use App\Models\State;
use Illuminate\Http\JsonResponse;

final class StatesController extends Controller
{
    use RanksSearchResults;

    public function __invoke(SearchStatesRequest $request): JsonResponse
    {
        $search = $request->validated('search');

        $results = State::query()
            ->where('country_id', $request->validated('country_id'))
            ->when($search, fn ($query) => $query->where('name', 'like', "%$search%"))
            ->get(['id', 'name'])
            ->map(fn (State $state): array => ['id' => $state->id, 'name' => $state->name]);

        return response()->json(['data' => $this->rankAndCap($results, $search)]);
    }
}
