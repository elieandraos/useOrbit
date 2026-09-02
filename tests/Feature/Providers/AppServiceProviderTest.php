<?php

declare(strict_types=1);

use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

test('production environment enforces strong password rules', function () {
    app()->instance('env', 'production');
    app()->instance(UncompromisedVerifier::class, new class implements UncompromisedVerifier
    {
        public function verify($data): bool
        {
            return true;
        }
    });

    $rule = Password::default();

    $weak = Validator::make(['password' => 'password1'], ['password' => $rule]);
    expect($weak->fails())->toBeTrue();

    $strong = Validator::make(['password' => 'Str0ng!Passw0rd'], ['password' => $rule]);
    expect($strong->fails())->toBeFalse();
});

test('non-production environment falls back to the default password rule', function () {
    app()->instance('env', 'testing');

    $rule = Password::default();

    $validator = Validator::make(['password' => 'password'], ['password' => $rule]);
    expect($validator->fails())->toBeFalse();
});
