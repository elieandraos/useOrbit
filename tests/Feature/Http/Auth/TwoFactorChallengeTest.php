<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Inertia\Testing\AssertableInertia as Assert;
use PragmaRX\Google2FA\Google2FA;

test('renders the challenge page for a partially authenticated user', function () {
    $user = User::factory()->withOrganization()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $this->get(route('two-factor.login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('auth/TwoFactorChallenge'));
});

test('authenticates with a valid two factor code', function () {
    $user = User::factory()->withOrganization()->withTwoFactor()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    $secret = Crypt::decrypt($user->two_factor_secret);

    /** @noinspection PhpUnhandledExceptionInspection */
    $validCode = app(Google2FA::class)->getCurrentOtp($secret);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $this->assertGuest();

    $this->post(route('two-factor.login.store'), ['code' => $validCode])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('authenticates with a valid recovery code', function () {
    $recoveryCode = 'test-recovery-code';

    /** @noinspection PhpUnhandledExceptionInspection */
    $user = User::factory()->withOrganization()->create([
        'two_factor_secret' => Crypt::encrypt(app(Google2FA::class)->generateSecretKey()),
        'two_factor_recovery_codes' => Crypt::encrypt(json_encode([$recoveryCode])),
        'two_factor_confirmed_at' => now(),
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $this->post(route('two-factor.login.store'), ['recovery_code' => $recoveryCode])
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('rejects an invalid two factor code', function () {
    $user = User::factory()->withOrganization()->withTwoFactor()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('two-factor.login'));

    $this->post(route('two-factor.login.store'), ['code' => '000000'])
        ->assertRedirect(route('two-factor.login'))
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});
