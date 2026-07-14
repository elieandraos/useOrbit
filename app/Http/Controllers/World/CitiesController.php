<?php

declare(strict_types=1);

namespace App\Http\Controllers\World;

use App\Http\Controllers\Controller;
use App\Http\Controllers\World\Concerns\RanksSearchResults;
use App\Http\Requests\World\SearchCitiesRequest;
use Illuminate\Http\JsonResponse;
use Nnjeim\World\World;

final class CitiesController extends Controller
{
    use RanksSearchResults;

    public function __invoke(SearchCitiesRequest $request): JsonResponse
    {
        $search = $request->validated('search');

        $result = World::cities([
            'filters' => ['state_id' => $request->validated('state_id')],
            'search' => $search !== null && $search !== '' ? $search : null,
        ]);

        return response()->json(['data' => $this->rankAndCap($result->data, $search)]);
    }
}
