<script setup lang="ts">
import FormField from '@/components/ui/form-field/FormField.vue';
import FormSection from '@/components/ui/form-section/FormSection.vue';
import Input from '@/components/ui/input/Input.vue';
import Select from '@/components/ui/select/Select.vue';
import type { PolicyCurrencyOption } from '@/types/policy';

defineProps<{
    currencies: PolicyCurrencyOption[];
    currencyCode: string;
    errors: Record<string, string | undefined>;
}>();

const currencyId = defineModel<string>('currencyId', { required: true });
const premiumAmount = defineModel<string>('premiumAmount', { required: true });
const discountAmount = defineModel<string>('discountAmount', {
    required: true,
});
</script>

<template>
    <FormSection
        title="Financials"
        subtitle="Currency, premium and any discount applied."
    >
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <FormField
                label="Currency"
                for="currency_id"
                required
                helper="Changing currency does not automatically convert amounts."
                :error="errors.currency_id"
            >
                <Select
                    id="currency_id"
                    v-model="currencyId"
                    name="currency_id"
                    placeholder="Select"
                >
                    <option
                        v-for="currency in currencies"
                        :key="currency.id"
                        :value="`${currency.id}`"
                    >
                        {{ currency.code }} — {{ currency.name }}
                    </option>
                </Select>
            </FormField>
            <FormField
                label="Premium amount"
                for="premium_amount"
                required
                :error="errors.premium_amount"
            >
                <Input
                    id="premium_amount"
                    v-model="premiumAmount"
                    name="premium_amount"
                    type="number"
                    min="0"
                    step="0.01"
                >
                    <template v-if="currencyCode" #leading>
                        <span class="text-xs font-medium text-tertiary">{{
                            currencyCode
                        }}</span>
                    </template>
                </Input>
            </FormField>
            <FormField
                label="Discount amount"
                for="discount_amount"
                optional
                :error="errors.discount_amount"
            >
                <Input
                    id="discount_amount"
                    v-model="discountAmount"
                    name="discount_amount"
                    type="number"
                    min="0"
                    step="0.01"
                >
                    <template v-if="currencyCode" #leading>
                        <span class="text-xs font-medium text-tertiary">{{
                            currencyCode
                        }}</span>
                    </template>
                </Input>
            </FormField>
        </div>
    </FormSection>
</template>
