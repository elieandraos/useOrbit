<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Support\Tenancy\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class CurrentOrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where($model->getTable().'.organization_id', app(OrganizationContext::class)->id());
    }
}
