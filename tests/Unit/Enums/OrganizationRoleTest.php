<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;

test('owner and admin are privileged', function () {
    expect(OrganizationRole::Owner->isPrivileged())->toBeTrue()
        ->and(OrganizationRole::Admin->isPrivileged())->toBeTrue();
});

test('member is not privileged', function () {
    expect(OrganizationRole::Member->isPrivileged())->toBeFalse();
});

test('invitableOptions returns admin and member but excludes owner', function () {
    expect(OrganizationRole::invitableOptions())->toBe([
        ['label' => 'Admin', 'value' => 'admin'],
        ['label' => 'Member', 'value' => 'member'],
    ]);
});
