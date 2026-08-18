<script setup lang="ts">
import { Form, Head, useHttp } from '@inertiajs/vue3';
import { Copy } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Tab, Tabs } from '@/components/ui/tabs';
import { useAuth } from '@/composables/useAuth';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editOrganization } from '@/routes/organization';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import {
    confirm as confirmTwoFactor,
    disable as disableTwoFactor,
    enable as enableTwoFactor,
    qrCode as twoFactorQrCode,
    recoveryCodes as twoFactorRecoveryCodes,
    regenerateRecoveryCodes,
    secretKey as twoFactorSecretKey,
} from '@/routes/two-factor';

type Props = {
    passwordRules: string;
    canManageTwoFactor: boolean;
    twoFactorEnabled?: boolean;
    requiresConfirmation?: boolean;
    organizationRequiresTwoFactor: boolean;
};

const props = defineProps<Props>();
const { isOwner } = useAuth();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: editSecurity(),
            },
        ],
    },
});

const settingUp = ref(false);
const qrCodeSvg = ref<string | null>(null);
const secretKey = ref<string | null>(null);
const showRecoveryCodes = ref(false);
const recoveryCodes = ref<string[] | null>(null);
const regeneratingCodes = ref(false);

function startSetup(): void {
    if (props.requiresConfirmation === false) {
        return;
    }

    settingUp.value = true;

    useHttp({}).get(twoFactorQrCode().url, {
        onSuccess: (response) => {
            qrCodeSvg.value = (response as { svg: string }).svg;
        },
        onError: () => {
            toast.error("Couldn't load the QR code.");
        },
    });

    useHttp({}).get(twoFactorSecretKey().url, {
        onSuccess: (response) => {
            secretKey.value = (response as { secretKey: string }).secretKey;
        },
        onError: () => {
            toast.error("Couldn't load the setup key.");
        },
    });
}

function cancelSetup(): void {
    settingUp.value = false;
    qrCodeSvg.value = null;
    secretKey.value = null;
}

function fetchRecoveryCodes(): void {
    useHttp({}).get(twoFactorRecoveryCodes().url, {
        onSuccess: (response) => {
            recoveryCodes.value = response as string[];
            regeneratingCodes.value = false;
        },
        onError: () => {
            toast.error("Couldn't load recovery codes.");
            regeneratingCodes.value = false;
        },
    });
}

function onConfirmed(): void {
    cancelSetup();
    showRecoveryCodes.value = true;
    fetchRecoveryCodes();
}

function onDisabled(): void {
    cancelSetup();
    showRecoveryCodes.value = false;
    recoveryCodes.value = null;
}

function revealRecoveryCodes(): void {
    showRecoveryCodes.value = true;
    fetchRecoveryCodes();
}

async function copy(value: string): Promise<void> {
    await navigator.clipboard.writeText(value);
    toast.success('Copied to clipboard.');
}
</script>

<template>
    <Head title="Security settings" />

    <div>
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <Tabs>
            <Tab :href="editProfile()">Profile</Tab>
            <Tab :href="editSecurity()">Security</Tab>
            <Tab :href="editAppearance()">Appearance</Tab>
            <Tab v-if="isOwner" :href="editOrganization()">Organization</Tab>
        </Tabs>

        <section class="max-w-xl space-y-12 py-8">
            <h1 class="sr-only">Security settings</h1>

            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Update password"
                    description="Ensure your account is using a long, random password to stay secure"
                />

                <Form
                    v-bind="SecurityController.update.form()"
                    :options="{
                        preserveScroll: true,
                    }"
                    reset-on-success
                    :reset-on-error="[
                        'password',
                        'password_confirmation',
                        'current_password',
                    ]"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="current_password">Current password</Label>
                        <PasswordInput
                            id="current_password"
                            name="current_password"
                            class="mt-1 block w-full"
                            autocomplete="current-password"
                            placeholder="Current password"
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">New password</Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="New password"
                            :passwordrules="props.passwordRules"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation"
                            >Confirm password</Label
                        >
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            class="mt-1 block w-full"
                            autocomplete="new-password"
                            placeholder="Confirm password"
                            :passwordrules="props.passwordRules"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-password-button"
                        >
                            Save
                        </Button>
                    </div>
                </Form>
            </div>

            <div
                v-if="canManageTwoFactor"
                class="space-y-6 border-t border-border-subtle pt-8"
            >
                <Heading
                    variant="small"
                    title="Two-factor authentication"
                    description="Add an extra layer of security to your account using an authenticator app"
                />

                <p
                    v-if="!organizationRequiresTwoFactor && !twoFactorEnabled"
                    class="text-[13px] text-tertiary"
                >
                    We recommend enabling two-factor authentication to keep your
                    account secure.
                </p>

                <template v-if="twoFactorEnabled">
                    <Badge tone="success" dot>Enabled</Badge>

                    <div v-if="showRecoveryCodes" class="space-y-3">
                        <p class="text-[13px] text-tertiary">
                            Store these recovery codes in a secure password
                            manager. They can be used to recover access if your
                            two-factor device is lost.
                        </p>
                        <div
                            v-if="recoveryCodes"
                            class="grid grid-cols-2 gap-2 rounded-lg border border-border bg-sunken p-4 font-mono text-sm"
                        >
                            <span v-for="code in recoveryCodes" :key="code">{{
                                code
                            }}</span>
                        </div>
                        <Spinner v-else />
                        <div class="flex gap-3">
                            <Button
                                v-if="recoveryCodes"
                                variant="secondary"
                                size="sm"
                                type="button"
                                :disabled="regeneratingCodes"
                                @click="copy(recoveryCodes!.join('\n'))"
                            >
                                Copy codes
                            </Button>
                            <Form
                                v-bind="regenerateRecoveryCodes.form()"
                                :options="{ preserveScroll: true }"
                                v-slot="{ processing: regenerating }"
                                @start="regeneratingCodes = true"
                                @success="fetchRecoveryCodes"
                                @error="regeneratingCodes = false"
                            >
                                <Button
                                    variant="secondary"
                                    size="sm"
                                    type="submit"
                                    :disabled="
                                        regenerating || regeneratingCodes
                                    "
                                >
                                    Regenerate codes
                                </Button>
                            </Form>
                        </div>
                    </div>
                    <Button
                        v-else
                        variant="secondary"
                        size="sm"
                        type="button"
                        @click="revealRecoveryCodes"
                    >
                        View recovery codes
                    </Button>

                    <Form
                        v-bind="disableTwoFactor.form()"
                        :options="{ preserveScroll: true }"
                        v-slot="{ processing: disabling }"
                        @success="onDisabled"
                    >
                        <Button
                            variant="destructive"
                            size="sm"
                            type="submit"
                            :disabled="disabling"
                        >
                            <Spinner v-if="disabling" />
                            Disable
                        </Button>
                    </Form>
                </template>

                <template v-else-if="settingUp">
                    <div v-if="qrCodeSvg" class="space-y-4">
                        <p class="text-[13px] text-tertiary">
                            Scan this QR code with your authenticator app, or
                            enter the setup key manually.
                        </p>
                        <!-- eslint-disable-next-line vue/no-v-html -->
                        <div
                            class="w-fit rounded-lg border border-border bg-white p-3"
                            v-html="qrCodeSvg"
                        />
                        <div v-if="secretKey" class="flex items-center gap-2">
                            <code
                                class="rounded bg-sunken px-2 py-1 font-mono text-xs"
                                >{{ secretKey }}</code
                            >
                            <button
                                type="button"
                                class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-secondary transition-colors hover:bg-sunken"
                                @click="copy(secretKey!)"
                            >
                                <Copy class="size-3.5" />
                            </button>
                        </div>

                        <Form
                            v-bind="confirmTwoFactor.form()"
                            :options="{ preserveScroll: true }"
                            error-bag="confirmTwoFactorAuthentication"
                            v-slot="{ errors, processing: confirming }"
                            @success="onConfirmed"
                            class="grid max-w-xs gap-2"
                        >
                            <Label for="two_factor_code"
                                >Confirmation code</Label
                            >
                            <Input
                                id="two_factor_code"
                                name="code"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                placeholder="123456"
                                required
                            />
                            <InputError :message="errors.code" />
                            <div class="flex items-center gap-3 pt-1">
                                <Button
                                    type="submit"
                                    size="sm"
                                    :disabled="confirming"
                                >
                                    <Spinner v-if="confirming" />
                                    Confirm
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    type="button"
                                    @click="cancelSetup"
                                >
                                    Cancel
                                </Button>
                            </div>
                        </Form>
                    </div>
                    <Spinner v-else />
                </template>

                <Form
                    v-else
                    v-bind="enableTwoFactor.form()"
                    :options="{ preserveScroll: true }"
                    v-slot="{ processing: enabling }"
                    @success="startSetup"
                >
                    <Button type="submit" size="sm" :disabled="enabling">
                        <Spinner v-if="enabling" />
                        Enable two-factor authentication
                    </Button>
                </Form>
            </div>
        </section>
    </div>
</template>
