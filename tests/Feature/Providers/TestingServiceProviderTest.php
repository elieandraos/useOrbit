<?php

declare(strict_types=1);

use App\Providers\TestingServiceProvider;

test('boot does nothing outside of the unit testing environment', function () {
    app()->instance('env', 'local');

    $provider = new TestingServiceProvider(app());

    expect(fn () => $provider->boot())->not->toThrow(Throwable::class);
});
