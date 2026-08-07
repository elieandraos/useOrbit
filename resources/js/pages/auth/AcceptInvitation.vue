<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/OrganizationInvitations/AcceptOrganizationInvitationController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Spinner } from '@/components/ui/spinner';

defineProps<{
    token: string;
    organization: string;
    invitedBy: string | null;
    role: string;
    name: string;
    email: string;
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Accept your invitation',
        description: 'Set a password to join the organization',
    },
});
</script>

<template>
    <Head title="Accept invitation" />

    <Form
        v-bind="store.form(token)"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="flex flex-col items-center gap-1 text-center">
                <p class="text-sm text-secondary">
                    You've been invited to join
                </p>
                <p class="text-2xl font-semibold tracking-tight text-primary">
                    {{ organization }}
                </p>
            </div>

            <Separator />

            <div class="flex flex-col items-center gap-2">
                <div
                    class="flex flex-wrap items-center justify-center gap-2 text-sm text-secondary"
                >
                    <template v-if="invitedBy">
                        <Avatar :name="invitedBy" :size="26" />
                        <span class="font-semibold text-primary">{{
                            invitedBy
                        }}</span>
                        <span>invited you as</span>
                    </template>
                    <span v-else>You've been invited as</span>
                    <Badge tone="neutral">{{ role }}</Badge>
                </div>
                <p class="text-xs text-tertiary">
                    This invitation is for {{ name }} ({{ email }})
                </p>
            </div>

            <Separator />

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="2"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :tabindex="3"
                :disabled="processing"
            >
                <Spinner v-if="processing" />
                Join organization
            </Button>
        </div>

        <p class="text-center text-xs text-tertiary">
            By accepting, you agree to join this organization's workspace.
        </p>
    </Form>
</template>
