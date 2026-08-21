<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use PragmaRX\Google2FA\Google2FA;

test('enabling two factor authentication generates a secret and recovery codes', function () {
    $user = User::factory()->withOrganization()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.enable'))
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_recovery_codes)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});

test('enabling two factor authentication requires password confirmation', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->post(route('two-factor.enable'))
        ->assertRedirect(route('password.confirm'));

    expect($user->fresh()->two_factor_secret)->toBeNull();
});

test('confirming two factor authentication with a valid code enables it', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.enable'));

    /** @noinspection PhpUnhandledExceptionInspection */
    $secret = Crypt::decrypt($user->fresh()->two_factor_secret);

    /** @noinspection PhpUnhandledExceptionInspection */
    $validCode = app(Google2FA::class)->getCurrentOtp($secret);

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.confirm'), ['code' => $validCode])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->two_factor_confirmed_at)->not->toBeNull();
});

test('confirming two factor authentication with an invalid code fails', function () {
    $user = User::factory()->withOrganization()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.enable'));

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.confirm'), ['code' => '000000'])
        ->assertSessionHasErrors('code', null, 'confirmTwoFactorAuthentication');

    expect($user->fresh()->two_factor_confirmed_at)->toBeNull();
});

test('disabling two factor authentication clears all two factor columns', function () {
    $user = User::factory()->withOrganization()->withTwoFactor()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->delete(route('two-factor.disable'))
        ->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});

test('regenerating recovery codes replaces the stored codes', function () {
    $user = User::factory()->withOrganization()->withTwoFactor()->create();
    $originalCodes = $user->two_factor_recovery_codes;

    /** @noinspection PhpUnhandledExceptionInspection */
    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.regenerate-recovery-codes'))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->two_factor_recovery_codes)->not->toBe($originalCodes);
});
