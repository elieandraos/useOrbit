<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Copy, MoreHorizontal, Pencil, Search, Tag, Trash2 } from '@lucide/vue';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Separator } from '@/components/ui/separator';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import basicLinksCode from './snippets/basic-links.md?raw';
import withCheckboxesCode from './snippets/with-checkboxes.md?raw';
import withIconsCode from './snippets/with-icons.md?raw';
import withSearchCode from './snippets/with-search.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'basic-links', label: 'Basic links' },
    { id: 'with-icons', label: 'With icons' },
    { id: 'with-search', label: 'With search input' },
    { id: 'with-checkboxes', label: 'With checkboxes' },
];

const views = ref<Record<string, ViewMode>>(
    Object.fromEntries(sections.map((s) => [s.id, 'preview'])),
);

const codeSnippets: Record<string, string> = {
    'basic-links': basicLinksCode,
    'with-icons': withIconsCode,
    'with-search': withSearchCode,
    'with-checkboxes': withCheckboxesCode,
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

const filterOptions = [
    { value: 'active', label: 'Active' },
    { value: 'pending', label: 'Pending' },
    { value: 'closed', label: 'Closed' },
];

const selectedFilters = ref<string[]>(['active']);

function toggleFilter(value: string) {
    const idx = selectedFilters.value.indexOf(value);

    if (idx === -1) {
        selectedFilters.value.push(value);
    } else {
        selectedFilters.value.splice(idx, 1);
    }
}
</script>

<template>
    <Head title="DropMenu — Design Foundation" />

    <h1 class="mb-10 text-2xl font-semibold">DropMenu</h1>

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
                class="flex h-[300px] items-start justify-start overflow-visible rounded-lg border border-border p-6"
                style="background: #f8f8f8"
            >
                <!-- Basic links -->
                <div v-if="section.id === 'basic-links'">
                    <DropMenu>
                        <template #trigger>
                            <Button variant="secondary">Options</Button>
                        </template>
                        <DropMenuItem href="/design-foundation"
                            >Settings</DropMenuItem
                        >
                        <DropMenuItem href="/design-foundation"
                            >Profile</DropMenuItem
                        >
                        <Separator class="my-1" />
                        <DropMenuItem>Log out</DropMenuItem>
                    </DropMenu>
                </div>

                <!-- With icons -->
                <div v-else-if="section.id === 'with-icons'">
                    <DropMenu>
                        <template #trigger>
                            <button
                                class="flex items-center justify-center rounded-md p-1.5 transition-colors hover:bg-sunken"
                            >
                                <MoreHorizontal class="size-4 text-secondary" />
                            </button>
                        </template>
                        <DropMenuItem href="/design-foundation">
                            <template #leading
                                ><Pencil class="size-4"
                            /></template>
                            Edit
                        </DropMenuItem>
                        <DropMenuItem href="/design-foundation">
                            <template #leading
                                ><Copy class="size-4"
                            /></template>
                            Duplicate
                        </DropMenuItem>
                        <Separator class="my-1" />
                        <DropMenuItem danger>
                            <template #leading
                                ><Trash2 class="size-4"
                            /></template>
                            Delete
                        </DropMenuItem>
                    </DropMenu>
                </div>

                <!-- With search -->
                <div v-else-if="section.id === 'with-search'">
                    <DropMenu align="start">
                        <template #trigger>
                            <Button variant="secondary">
                                <template #leading
                                    ><Tag class="size-4"
                                /></template>
                                Add tag
                            </Button>
                        </template>
                        <div
                            class="mx-1 mb-1 flex items-center gap-1.5 rounded-md border border-border px-2 py-1.5"
                        >
                            <Search class="size-3.5 shrink-0 text-tertiary" />
                            <input
                                type="text"
                                placeholder="Find or create…"
                                class="flex-1 bg-transparent text-sm outline-none placeholder:text-tertiary"
                                @click.stop
                            />
                        </div>
                        <DropMenuItem>Marketing</DropMenuItem>
                        <DropMenuItem>Renewal</DropMenuItem>
                        <DropMenuItem>VIP</DropMenuItem>
                    </DropMenu>
                </div>

                <!-- With checkboxes -->
                <div v-else-if="section.id === 'with-checkboxes'">
                    <DropMenu>
                        <template #trigger>
                            <Button variant="secondary">Filter</Button>
                        </template>
                        <div
                            class="px-2 py-1.5 font-mono text-[10.5px] tracking-widest text-tertiary uppercase"
                        >
                            Status
                        </div>
                        <label
                            v-for="option in filterOptions"
                            :key="option.value"
                            class="flex cursor-pointer items-center gap-2 rounded-[6px] px-2 py-1.5 text-sm text-primary hover:bg-sunken"
                            @click.stop
                        >
                            <Checkbox
                                :model-value="
                                    selectedFilters.includes(option.value)
                                "
                                @update:model-value="toggleFilter(option.value)"
                            />
                            {{ option.label }}
                        </label>
                    </DropMenu>
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
