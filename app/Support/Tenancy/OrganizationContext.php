<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use LogicException;

final class OrganizationContext
{
    private ?int $organizationId = null;

    public function set(int $organizationId): void
    {
        $this->organizationId = $organizationId;
    }

    public function id(): int
    {
        return $this->organizationId
            ?? throw new LogicException('No organization context has been established.');
    }
}
