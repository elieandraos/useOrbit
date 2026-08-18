<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import OrganizationController from '@/actions/App/Http/Controllers/Settings/OrganizationController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import Switch from '@/components/ui/switch/Switch.vue';
import SwitchField from '@/components/ui/switch/SwitchField.vue';
import { Tab, Tabs } from '@/components/ui/tabs';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editOrganization } from '@/routes/organization';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

const props = defineProps<{
    twoFactorRequired: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Organization settings',
                href: editOrganization(),
            },
        ],
    },
});

const twoFactorRequired = ref(props.twoFactorRequired);
</script>

<template>
    <Head title="Organization settings" />

    <div>
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <Tabs>
            <Tab :href="editProfile()">Profile</Tab>
            <Tab :href="editSecurity()">Security</Tab>
            <Tab :href="editAppearance()">Appearance</Tab>
            <Tab :href="editOrganization()">Organization</Tab>
        </Tabs>

        <section class="max-w-xl space-y-12 py-8">
            <h1 class="sr-only">Organization settings</h1>

            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Security"
                    description="Manage security requirements for everyone in this organization"
                />

                <Form
                    v-bind="OrganizationController.update.form()"
                    :options="{ preserveScroll: true }"
                    class="space-y-6"
                    v-slot="{ processing }"
                >
                    <SwitchField
                        label="Require two-factor authentication"
                        description="Every member must enroll in two-factor authentication before they can access this organization."
                        v-slot="{ id }"
                    >
                        <Switch
                            :id="id"
                            v-model="twoFactorRequired"
                            name="two_factor_required"
                        />
                    </SwitchField>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-organization-button"
                        >
                            Save
                        </Button>
                    </div>
                </Form>
            </div>
        </section>
    </div>
</template>
