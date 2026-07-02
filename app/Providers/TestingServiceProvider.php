<?php

declare(strict_types=1);

namespace App\Providers;

use Closure;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

/**
 * @method assertInertia(Closure $param)
 * @method has(string $key)
 * @method prop(string $key)
 */
class TestingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if (! $this->app->runningUnitTests()) {
            return;
        }

        AssertableInertia::macro('hasResource', function (string $key, JsonResource $resource) {
            $this->has($key);
            expect($this->prop($key))->toEqual($resource->response()->getData(true));

            return $this;
        });

        AssertableInertia::macro('hasPaginatedResource', function (string $key, ResourceCollection $collection) {
            $expectedData = $collection->response()->getData(true);
            expect($this->prop($key))->toHaveKeys(['data', 'links', 'meta'])
                ->and($this->prop($key)['data'])->toEqual($expectedData['data']);

            return $this;
        });

        TestResponse::macro('assertHasResource', function (string $key, JsonResource $resource) {
            return $this->assertInertia(function ($inertia) use ($key, $resource) {
                $inertia->hasResource($key, $resource);
            });
        });

        TestResponse::macro('assertHasPaginatedResource', function (string $key, ResourceCollection $resource) {
            return $this->assertInertia(function ($inertia) use ($key, $resource) {
                $inertia->hasPaginatedResource($key, $resource);
            });
        });

        TestResponse::macro('assertHasInertiaFlash', function (string $type, string $message) {
            return $this->assertSessionHas('inertia.flash_data', [
                'toast' => ['type' => $type, 'message' => $message],
            ]);
        });
    }
}
