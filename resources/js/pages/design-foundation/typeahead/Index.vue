<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import { Typeahead } from '@/components/ui/typeahead';
import type { TypeaheadOption } from '@/components/ui/typeahead';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import asyncCode from './snippets/async.md?raw';
import disabledCode from './snippets/disabled.md?raw';
import initialLabelCode from './snippets/initial-label.md?raw';
import loadingCode from './snippets/loading.md?raw';
import staticCode from './snippets/static.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'static', label: 'Static options' },
    { id: 'async', label: 'Async search' },
    { id: 'loading', label: 'Loading state' },
    { id: 'disabled', label: 'Disabled' },
    { id: 'initial-label', label: 'Pre-filled value' },
];

const views = ref<Record<string, ViewMode>>(
    Object.fromEntries(sections.map((s) => [s.id, 'preview'])),
);

const codeSnippets: Record<string, string> = {
    static: staticCode,
    async: asyncCode,
    loading: loadingCode,
    disabled: disabledCode,
    'initial-label': initialLabelCode,
};

const highlighted = ref<Record<string, string>>({});

onMounted(async () => {
    const entries = await Promise.all(
        Object.entries(codeSnippets).map(async ([id, code]) => {
            const html = await codeToHtml(code, {
                lang: 'vue',
                theme: 'github-dark',
            });

            return [id, html] as [string, string];
        }),
    );

    highlighted.value = Object.fromEntries(entries);
});

const teamMembers: TypeaheadOption[] = [
    { value: 1, label: 'Marie Khalil' },
    { value: 2, label: 'Karim Fares' },
    { value: 3, label: 'Nour Abdallah' },
    { value: 4, label: 'Sami Haddad' },
    { value: 5, label: 'Layla Mansour' },
];

const assignee = ref<number | string | null>(null);

// Stand-in for an endpoint like `GET /clients/search?q=`.
const clients: TypeaheadOption[] = [
    { value: 1, label: 'Marie Khalil' },
    { value: 2, label: 'Karim Fares' },
    { value: 3, label: 'Nour Abdallah' },
    { value: 4, label: 'Sami Haddad' },
    { value: 5, label: 'Layla Mansour' },
    { value: 6, label: 'Rami Choueiri' },
    { value: 7, label: 'Dana Saab' },
    { value: 8, label: 'Elie Tannous' },
];

function searchClients(query: string): Promise<TypeaheadOption[]> {
    return new Promise((resolve) => {
        setTimeout(() => {
            const needle = query.trim().toLowerCase();

            resolve(
                needle
                    ? clients.filter((client) =>
                          client.label.toLowerCase().includes(needle),
                      )
                    : clients,
            );
        }, 600);
    });
}

const client = ref<number | string | null>(null);
const disabledValue = ref<number | string | null>(null);
const prefilledClient = ref<number | string | null>(5);
</script>

<template>
    <Head title="Typeahead — Design Foundation" />

    <h1 class="mb-10 text-2xl font-semibold">Typeahead</h1>

    <div class="flex flex-col gap-10">
        <div
            v-for="section in sections"
            :id="section.id"
            :key="section.id"
            class="flex flex-col gap-3"
        >
            <!-- Section header -->
            <div class="flex items-center justify-between">
                <p
                    class="font-mono text-xs tracking-widest text-tertiary uppercase"
                >
                    {{ section.label }}
                </p>

                <div
                    class="flex items-center gap-0.5 rounded-md border border-border p-0.5 text-xs"
                >
                    <button
                        class="rounded px-2.5 py-1 transition-colors"
                        :class="
                            views[section.id] === 'preview'
                                ? 'bg-surface text-primary'
                                : 'text-tertiary hover:text-secondary'
                        "
                        @click="views[section.id] = 'preview'"
                    >
                        Preview
                    </button>
                    <button
                        class="rounded px-2.5 py-1 transition-colors"
                        :class="
                            views[section.id] === 'code'
                                ? 'bg-surface text-primary'
                                : 'text-tertiary hover:text-secondary'
                        "
                        @click="views[section.id] = 'code'"
                    >
                        Code
                    </button>
                </div>
            </div>

            <!-- Preview panel -->
            <div
                v-if="views[section.id] === 'preview'"
                class="h-[300px] overflow-visible rounded-lg border border-border p-6"
                style="background: #f8f8f8"
            >
                <div
                    v-if="section.id === 'static'"
                    class="flex max-w-sm flex-col gap-3"
                >
                    <Typeahead
                        v-model="assignee"
                        :options="teamMembers"
                        placeholder="Assign to"
                    />
                    <p class="text-sm text-secondary">Value: {{ assignee }}</p>
                </div>

                <div
                    v-else-if="section.id === 'async'"
                    class="flex max-w-sm flex-col gap-3"
                >
                    <Typeahead
                        v-model="client"
                        :search="searchClients"
                        placeholder="Search clients…"
                    />
                    <p class="text-sm text-secondary">Value: {{ client }}</p>
                    <p class="text-xs text-tertiary">
                        Simulates a 600ms network round trip, debounced as you
                        type.
                    </p>
                </div>

                <div v-else-if="section.id === 'loading'" class="max-w-sm">
                    <Typeahead
                        :options="teamMembers"
                        placeholder="Search clients…"
                        loading
                    />
                </div>

                <div v-else-if="section.id === 'disabled'" class="max-w-sm">
                    <Typeahead
                        v-model="disabledValue"
                        :options="teamMembers"
                        placeholder="Assign to"
                        disabled
                    />
                </div>

                <div
                    v-else-if="section.id === 'initial-label'"
                    class="flex max-w-sm flex-col gap-3"
                >
                    <Typeahead
                        v-model="prefilledClient"
                        :search="searchClients"
                        initial-label="Layla Mansour"
                        placeholder="Search clients…"
                    />
                    <p class="text-xs text-tertiary">
                        Value is already known (id 5); label renders before the
                        first search resolves.
                    </p>
                </div>
            </div>

            <!-- Code panel -->
            <div
                v-else
                class="h-[300px] overflow-hidden overflow-y-auto rounded-lg border border-border text-sm [&>pre]:!m-0 [&>pre]:min-h-full [&>pre]:p-5 [&>pre]:leading-relaxed"
                v-html="highlighted[section.id] ?? ''"
            />
        </div>
    </div>
</template>
