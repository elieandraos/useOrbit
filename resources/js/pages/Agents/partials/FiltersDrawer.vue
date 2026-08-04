<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { SearchIcon } from '@lucide/vue';
import { ref, watch } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import Drawer from '@/components/ui/drawer/Drawer.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
import Switch from '@/components/ui/switch/Switch.vue';
import SwitchField from '@/components/ui/switch/SwitchField.vue';
import { index as agentsIndex } from '@/routes/agents';

function isTruthy(value: string | number | boolean | null): boolean {
    return value === true || value === 1 || value === '1';
}

interface Filters {
    search: string | null;
    archived: string | number | boolean | null;
}

const props = defineProps<{
    filters: Filters;
}>();

const open = defineModel<boolean>('open', { default: false });

const search = ref(props.filters.search ?? '');
const archived = ref(isTruthy(props.filters.archived));
const formErrors = ref<Record<string, string>>({});

watch(open, (isOpen) => {
    formErrors.value = {};

    if (!isOpen) {
        return;
    }

    search.value = props.filters.search ?? '';
    archived.value = isTruthy(props.filters.archived);
});

function applyFilters() {
    const query: Record<string, string | number> = {};

    if (search.value) {
        query.search = search.value;
    }

    if (archived.value) {
        query.archived = 1;
    }

    formErrors.value = {};
    router.get(
        agentsIndex.url({ query }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                open.value = false;
            },
            onError: (errors) => {
                formErrors.value = errors as Record<string, string>;
            },
        },
    );
}

function clearFilters() {
    open.value = false;
    router.get(agentsIndex.url());
}
</script>

<template>
    <Drawer v-model:open="open" title="Agents Filters">
        <div class="flex flex-col gap-5">
            <FormField
                label="Search"
                for="filter_search"
                :error="formErrors.search"
            >
                <Input
                    id="filter_search"
                    v-model="search"
                    placeholder="Name, phone, or email"
                >
                    <template #leading>
                        <SearchIcon />
                    </template>
                </Input>
            </FormField>

            <SwitchField
                label="Show archived agents"
                description="View agents that have been archived."
                v-slot="{ id }"
            >
                <Switch :id="id" v-model="archived" />
            </SwitchField>
        </div>

        <template #footer>
            <Button variant="ghost" size="md" @click="clearFilters"
                >Clear filters</Button
            >
            <div class="flex-1" />
            <Button variant="primary" size="md" @click="applyFilters"
                >Apply filters</Button
            >
        </template>
    </Drawer>
</template>
