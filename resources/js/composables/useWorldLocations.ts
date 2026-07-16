import type { Ref } from 'vue';
import { ref, watch } from 'vue';
import type { TypeaheadOption } from '@/components/ui/typeahead';
import { index as statesIndex } from '@/routes/world/states';

type WorldLocationRow = { id: number; name: string };

function toOptions(rows: WorldLocationRow[]): TypeaheadOption[] {
    return rows.map((row) => ({ value: row.id, label: row.name }));
}

export type UseStateOptionsReturn = {
    options: Ref<TypeaheadOption[]>;
    loading: Ref<boolean>;
};

export function useStateOptions(
    countryId: Ref<number | null>,
): UseStateOptionsReturn {
    const options = ref<TypeaheadOption[]>([]);
    const loading = ref(false);

    watch(
        countryId,
        async (id) => {
            if (!id) {
                options.value = [];

                return;
            }

            loading.value = true;

            try {
                const response = await fetch(
                    statesIndex.url({ query: { country_id: id } }),
                );

                if (!response.ok) {
                    options.value = [];

                    return;
                }

                const { data } = await response.json();

                options.value = toOptions(data);
            } finally {
                loading.value = false;
            }
        },
        { immediate: true },
    );

    return { options, loading };
}
