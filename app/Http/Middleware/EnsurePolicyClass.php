<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\PolicyClass;
use App\Models\Policy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsurePolicyClass
{
    /**
     * Abort with a 404 when the route-bound policy does not belong to the policy class the route addresses.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $class): Response
    {
        $policy = $request->route('policy');

        abort_unless($policy instanceof Policy && $policy->class === PolicyClass::from($class), 404);

        return $next($request);
    }
}
