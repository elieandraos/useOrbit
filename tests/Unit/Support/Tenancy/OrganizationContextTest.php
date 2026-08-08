<?php

declare(strict_types=1);

use App\Support\Tenancy\OrganizationContext;

test('id throws when no organization context has been set', function () {
    $context = new OrganizationContext;

    $context->id();
})->throws(LogicException::class, 'No organization context has been established.');

test('id returns the organization id after set', function () {
    $context = new OrganizationContext;

    $context->set(42);

    expect($context->id())->toBe(42);
});
