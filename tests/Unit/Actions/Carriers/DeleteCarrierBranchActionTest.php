<?php

declare(strict_types=1);

use App\Actions\Carriers\DeleteCarrierBranchAction;
use App\Models\CarrierBranch;

test('deletes the branch from the database', function () {
    $branch = CarrierBranch::factory()->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(DeleteCarrierBranchAction::class)->handle($branch);

    $this->assertModelMissing($branch);
});

test('does not affect other branches on the same carrier', function () {
    $branch = CarrierBranch::factory()->create();
    $otherBranch = CarrierBranch::factory()->forCarrier($branch->carrier)->create();

    /** @noinspection PhpUnhandledExceptionInspection */
    app(DeleteCarrierBranchAction::class)->handle($branch);

    $this->assertModelExists($otherBranch);
});
