<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Organizations\ProvisionOrganizationAction;
use App\Concerns\ProfileValidationRules;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\Validator;

#[Signature("organizations:provision
    {organization : The new organization's name}
    {owner-name : The first Owner's name}
    {owner-email : The first Owner's email address}")]
#[Description('Provision a new organization and its first Owner')]
final class ProvisionOrganization extends Command implements PromptsForMissingInput
{
    use ProfileValidationRules;

    /**
     * @throws \Throwable
     */
    public function handle(ProvisionOrganizationAction $provision): int
    {
        $validator = Validator::make([
            'organization' => $this->argument('organization'),
            'name' => $this->argument('owner-name'),
            'email' => $this->argument('owner-email'),
        ], [
            'organization' => ['required', 'string', 'max:255'],
            ...$this->profileRules(),
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        }

        /** @var array{organization: string, name: string, email: string} $attributes */
        $attributes = $validator->validated();

        ['owner' => $owner, 'token' => $token] = $provision->handle($attributes);

        $this->components->info('Organization provisioned.');
        $this->components->twoColumnDetail('Organization', $attributes['organization']);
        $this->components->twoColumnDetail('Owner email', $owner->email);
        $this->components->twoColumnDetail('Invitation URL', route('invitations.show', $token));

        return self::SUCCESS;
    }

    /**
     * @return array<string, string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'organization' => 'What is the name of the new organization?',
            'owner-name' => "What is the first Owner's name?",
            'owner-email' => "What is the first Owner's email address?",
        ];
    }
}
