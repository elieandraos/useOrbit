import type { ComputedRef, Ref } from 'vue';
import { computed } from 'vue';
import type { PolicyCurrencyOption } from '@/types/policy';

export type UsePolicyCurrencyReturn = {
    currencyCode: ComputedRef<string>;
};

/**
 * The currency a policy form starts with: the stored one on edit, otherwise the organization default.
 */
export function initialPolicyCurrencyId(
    storedCurrencyId: number | null | undefined,
    defaultCurrencyId: number | null | undefined,
): string {
    const initialCurrencyId = storedCurrencyId ?? defaultCurrencyId;

    return initialCurrencyId ? `${initialCurrencyId}` : '';
}

/**
 * The policy form's selected currency code, for labelling amounts.
 */
export function usePolicyCurrency(
    currencies: () => PolicyCurrencyOption[],
    currencyId: Ref<string>,
): UsePolicyCurrencyReturn {
    const currencyCode = computed(
        () =>
            currencies().find(
                (currency) => `${currency.id}` === currencyId.value,
            )?.code ?? '',
    );

    return { currencyCode };
}
