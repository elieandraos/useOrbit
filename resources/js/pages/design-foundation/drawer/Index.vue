<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Drawer } from '@/components/ui/drawer';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import { Select } from '@/components/ui/select';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import contentOnlyCode from './snippets/content-only.md?raw';
import filtersCode from './snippets/filters.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'filters', label: 'Filters drawer' },
    { id: 'content-only', label: 'Content-only drawer' },
];

const views = ref<Record<string, ViewMode>>(Object.fromEntries(sections.map((s) => [s.id, 'preview'])));

const codeSnippets: Record<string, string> = {
    filters: filtersCode,
    'content-only': contentOnlyCode,
};

const highlighted = ref<Record<string, string>>({});

onMounted(async () => {
    const entries = await Promise.all(
        Object.entries(codeSnippets).map(async ([id, code]) => {
            const html = await codeToHtml(code, { lang: 'vue', theme: 'github-dark' });

            return [id, html] as [string, string];
        }),
    );

    highlighted.value = Object.fromEntries(entries);
});

const filtersOpen = ref(false);
const search = ref('');
const gender = ref('Any');
const source = ref('');

function clearFilters() {
    search.value = '';
    gender.value = 'Any';
    source.value = '';
}

const contentOpen = ref(false);
</script>

<template>
    <Head title="Drawer — Design Foundation" />

    <h1 class="mb-10 text-2xl font-semibold">Drawer</h1>

    <div class="flex flex-col gap-10">
        <div v-for="section in sections" :id="section.id" :key="section.id" class="flex flex-col gap-3">
            <!-- Section header -->
            <div class="flex items-center justify-between">
                <p class="text-xs font-mono text-tertiary uppercase tracking-widest">{{ section.label }}</p>

                <div class="flex items-center gap-0.5 rounded-md border border-border p-0.5 text-xs">
                    <button
                        class="px-2.5 py-1 rounded transition-colors"
                        :class="views[section.id] === 'preview' ? 'bg-surface text-primary' : 'text-tertiary hover:text-secondary'"
                        @click="views[section.id] = 'preview'"
                    >
                        Preview
                    </button>
                    <button
                        class="px-2.5 py-1 rounded transition-colors"
                        :class="views[section.id] === 'code' ? 'bg-surface text-primary' : 'text-tertiary hover:text-secondary'"
                        @click="views[section.id] = 'code'"
                    >
                        Code
                    </button>
                </div>
            </div>

            <!-- Preview panel -->
            <div
                v-if="views[section.id] === 'preview'"
                class="relative rounded-lg border border-border p-6 h-[300px] flex items-center justify-center overflow-hidden"
                style="background: #f8f8f8"
            >
                <!-- Filters drawer -->
                <div v-if="section.id === 'filters'">
                    <Button @click="filtersOpen = true">Filters</Button>

                    <Drawer v-model:open="filtersOpen" title="Filters" description="Refine the client list">
                        <div class="flex flex-col gap-5">
                            <div class="flex flex-col gap-2">
                                <Label for="df-drawer-search">Search</Label>
                                <Input id="df-drawer-search" v-model="search" placeholder="Name, phone, or email" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label>Gender</Label>
                                <RadioChips v-model="gender" :options="['Any', 'Female', 'Male', 'Non-binary']" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="df-drawer-source">Lead source</Label>
                                <Select id="df-drawer-source" v-model="source" placeholder="Any source">
                                    <option value="referral">Referral</option>
                                    <option value="website">Website</option>
                                    <option value="partner">Partner</option>
                                </Select>
                            </div>
                        </div>

                        <template #footer>
                            <Button variant="ghost" @click="clearFilters">Clear filters</Button>
                            <div class="flex-1" />
                            <Button @click="filtersOpen = false">Apply filters</Button>
                        </template>
                    </Drawer>
                </div>

                <!-- Content-only drawer -->
                <div v-else-if="section.id === 'content-only'">
                    <Button variant="secondary" @click="contentOpen = true">Open panel</Button>

                    <Drawer v-model:open="contentOpen">
                        <p class="text-sm leading-relaxed text-secondary">
                            This drawer has no title or description — the header row still shows the close button, but the title
                            block is omitted entirely. Useful for previews or any panel where the content speaks for itself.
                        </p>

                        <template #footer>
                            <Button variant="secondary" @click="contentOpen = false">Close</Button>
                        </template>
                    </Drawer>
                </div>
            </div>

            <!-- Code panel -->
            <div
                v-else
                class="rounded-lg overflow-hidden border border-border text-sm h-[300px] overflow-y-auto [&>pre]:!m-0 [&>pre]:min-h-full [&>pre]:p-5 [&>pre]:leading-relaxed"
                v-html="highlighted[section.id] ?? ''"
            />
        </div>
    </div>
</template>
