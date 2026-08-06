<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/actions/App/Http/Controllers/OrganizationInvitations/AcceptOrganizationInvitationController';

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
            <div class="rounded-lg border border-border-subtle p-4 text-sm">
                <p class="font-medium text-primary">{{ organization }}</p>
                <p class="text-secondary">
                    {{ name }} ({{ email }}) &middot; invited as {{ role }}
                    <template v-if="invitedBy">by {{ invitedBy }}</template>
                </p>
            </div>

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
    </Form>
</template>
