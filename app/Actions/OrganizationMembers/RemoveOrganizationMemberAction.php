<?php

declare(strict_types=1);

namespace App\Actions\OrganizationMembers;

use App\Models\Agent;
use App\Models\Carrier;
use App\Models\Client;
use App\Models\Document;
use App\Models\Note;
use App\Models\Tag;
use App\Models\User;
use App\Support\Tenancy\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class RemoveOrganizationMemberAction
{
    public function __construct(private readonly OrganizationContext $organizationContext) {}

    /**
     * @throws \Throwable
     */
    public function handle(User $member, User $successor): void
    {
        DB::transaction(function () use ($member, $successor): void {
            $organizationId = $this->organizationContext->id();

            $this->reassign(Client::withTrashed(), $organizationId, $member, $successor, ['created_by', 'updated_by']);
            $this->reassign(Document::query(), $organizationId, $member, $successor, ['uploaded_by']);
            $this->reassign(Note::query(), $organizationId, $member, $successor, ['created_by']);
            $this->reassign(Tag::query(), $organizationId, $member, $successor, ['created_by', 'updated_by']);
            $this->reassign(Carrier::withTrashed(), $organizationId, $member, $successor, ['created_by', 'updated_by']);
            $this->reassign(Agent::withTrashed(), $organizationId, $member, $successor, ['created_by', 'updated_by']);

            $member->delete();
        });
    }

    /**
     * @param  Builder<Client|Document|Note|Tag|Carrier|Agent>  $query
     * @param  array<int, string>  $columns
     */
    private function reassign(Builder $query, ?int $organizationId, User $member, User $successor, array $columns): void
    {
        foreach ($columns as $column) {
            (clone $query)
                ->where('organization_id', $organizationId)
                ->where($column, $member->id)
                ->update([$column => $successor->id]);
        }
    }
}
