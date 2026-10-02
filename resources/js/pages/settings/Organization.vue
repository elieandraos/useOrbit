<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import OrganizationController from '@/actions/App/Http/Controllers/Settings/OrganizationController';
import OrganizationDetailsController from '@/actions/App/Http/Controllers/Settings/OrganizationDetailsController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
import Select from '@/components/ui/select/Select.vue';
import Switch from '@/components/ui/switch/Switch.vue';
import SwitchField from '@/components/ui/switch/SwitchField.vue';
import { Tab, Tabs } from '@/components/ui/tabs';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editOrganization } from '@/routes/organization';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

const props = defineProps<{
    name: string;
    defaultCountryId: number | null;
    defaultCurrencyId: number | null;
    twoFactorRequired: boolean;
    countries: { id: number; name: string }[];
    currencies: { id: number; code: string; name: string }[];
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

const name = ref(props.name);
const defaultCountryId = ref<number | string | null>(props.defaultCountryId);
const defaultCurrencyId = ref(props.defaultCurrencyId?.toString() ?? '');
const twoFactorRequired = ref(props.twoFactorRequired);

const countryOptions = computed<TypeaheadOption[]>(() => [
    { value: '', label: 'No default' },
    ...props.countries.map((country) => ({
        value: country.id,
        label: country.name,
    })),
]);
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
                    title="General"
                    description="Your organization's name and the defaults new records start with"
                />

                <Form
                    v-bind="OrganizationDetailsController.form()"
                    :options="{ preserveScroll: true }"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <FormField
                        label="Name"
                        for="name"
                        required
                        :error="errors.name"
                    >
                        <Input
                            id="name"
                            v-model="name"
                            name="name"
                            required
                            placeholder="Organization name"
                        />
                    </FormField>

                    <FormField
                        label="Default country"
                        for="default_country_id"
                        optional
                        helper="Pre-filled on new records. Changing it never changes existing records."
                        :error="errors.default_country_id"
                    >
                        <Typeahead
                            id="default_country_id"
                            v-model="defaultCountryId"
                            name="default_country_id"
                            :options="countryOptions"
                            placeholder="No default"
                        />
                    </FormField>

                    <FormField
                        label="Default currency"
                        for="default_currency_id"
                        optional
                        helper="Pre-selected on new policies. Changing it never changes existing policies."
                        :error="errors.default_currency_id"
                    >
                        <Select
                            id="default_currency_id"
                            v-model="defaultCurrencyId"
                            name="default_currency_id"
                        >
                            <option value="">No default</option>
                            <option
                                v-for="currency in currencies"
                                :key="currency.id"
                                :value="currency.id.toString()"
                            >
                                {{ currency.code }} — {{ currency.name }}
                            </option>
                        </Select>
                    </FormField>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-organization-details-button"
                        >
                            Save
                        </Button>
                    </div>
                </Form>
            </div>

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
