<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

/**
 * Reusable implementation template — my-laravel-stack.
 *
 * Target path in a consuming project: app/Providers/TestingServiceProvider.php
 *
 * Requires: inertiajs/inertia-laravel (for Inertia\Testing\AssertableInertia)
 * and Pest (for the expect() global used inside the macros below).
 *
 * Registration: add App\Providers\TestingServiceProvider::class to
 * bootstrap/providers.php. The runningUnitTests() guard in boot() means these
 * macros are registered only while the test suite runs, so the provider is
 * safe to register unconditionally alongside the application's other providers.
 *
 * Before installing: check whether app/Providers/TestingServiceProvider.php (or
 * an equivalent already registered in bootstrap/providers.php) exists in the
 * target project. Reconcile rather than overwrite it, and don't register the
 * class twice in bootstrap/providers.php.
 */
final class TestingServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        if (! $this->app->runningUnitTests()) {
            return;
        }

        AssertableInertia::macro('hasResource', function (string $key, JsonResource $resource) {
            /** @var AssertableInertia $this */
            $this->has($key);
            expect($this->prop($key))->toEqual($resource->response()->getData(true));

            return $this;
        });

        AssertableInertia::macro('hasPaginatedResource', function (string $key, ResourceCollection $collection) {
            /** @var AssertableInertia $this */
            $expectedData = $collection->response()->getData(true);
            expect($this->prop($key))->toHaveKeys(['data', 'links', 'meta'])
                ->and($this->prop($key)['data'])->toEqual($expectedData['data']);

            return $this;
        });

        TestResponse::macro('assertHasResource', function (string $key, JsonResource $resource) {
            /** @var TestResponse $this */
            return $this->assertInertia(function (AssertableInertia $inertia) use ($key, $resource) {
                $inertia->hasResource($key, $resource);
            });
        });

        TestResponse::macro('assertHasPaginatedResource', function (string $key, ResourceCollection $resource) {
            /** @var TestResponse $this */
            return $this->assertInertia(function (AssertableInertia $inertia) use ($key, $resource) {
                $inertia->hasPaginatedResource($key, $resource);
            });
        });

        TestResponse::macro('assertHasInertiaFlash', function (string $type, string $message) {
            /** @var TestResponse $this */
            return $this->assertSessionHas('inertia.flash_data', [
                'toast' => ['type' => $type, 'message' => $message],
            ]);
        });
    }
}
