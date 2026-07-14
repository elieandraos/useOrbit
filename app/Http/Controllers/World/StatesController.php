<?php

declare(strict_types=1);

namespace App\Http\Controllers\World;

use App\Http\Controllers\Controller;
use App\Http\Controllers\World\Concerns\RanksSearchResults;
use App\Http\Requests\World\SearchStatesRequest;
use Illuminate\Http\JsonResponse;
use Nnjeim\World\World;

final class StatesController extends Controller
{
    use RanksSearchResults;

    public function __invoke(SearchStatesRequest $request): JsonResponse
    {
        $search = $request->validated('search');

        $result = World::states([
            'filters' => ['country_id' => $request->validated('country_id')],
            'search' => $search !== null && $search !== '' ? $search : null,
        ]);

        return response()->json(['data' => $this->rankAndCap($result->data, $search)]);
    }
}
