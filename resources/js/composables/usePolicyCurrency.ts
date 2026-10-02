import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import type { PolicyCurrencyOption } from '@/types/policy';

export type UsePolicyCurrencyReturn = {
    currencyId: Ref<string>;
    currencyCode: ComputedRef<string>;
};

/**
 * The policy form's selected currency: the stored one on edit, otherwise the organization default, and its code for labelling amounts.
 */
export function usePolicyCurrency(
    currencies: () => PolicyCurrencyOption[],
    storedCurrencyId: number | null | undefined,
    defaultCurrencyId: number | null | undefined,
): UsePolicyCurrencyReturn {
    const initialCurrencyId = storedCurrencyId ?? defaultCurrencyId;
    const currencyId = ref(initialCurrencyId ? `${initialCurrencyId}` : '');

    const currencyCode = computed(
        () =>
            currencies().find(
                (currency) => `${currency.id}` === currencyId.value,
            )?.code ?? '',
    );

    return { currencyId, currencyCode };
}
