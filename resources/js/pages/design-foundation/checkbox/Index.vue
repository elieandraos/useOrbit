<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import Checkbox from '@/components/ui/checkbox/Checkbox.vue';
import Label from '@/components/ui/label/Label.vue';
import statesCode from './snippets/states.md?raw';
import multipleCode from './snippets/multiple.md?raw';
import disabledCode from './snippets/disabled.md?raw';
import reactivityCode from './snippets/reactivity.md?raw';
import labelCode from './snippets/label.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'states', label: 'States' },
    { id: 'multiple', label: 'Multiple checkboxes' },
    { id: 'disabled', label: 'Disabled' },
    { id: 'reactivity', label: 'Reactivity' },
    { id: 'label', label: 'Pairing with label' },
];

const views = ref<Record<string, ViewMode>>(Object.fromEntries(sections.map((s) => [s.id, 'preview'])));

const codeSnippets: Record<string, string> = {
    states: statesCode,
    multiple: multipleCode,
    disabled: disabledCode,
    reactivity: reactivityCode,
    label: labelCode,
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

const stateUnchecked = ref(false);
const stateChecked = ref(true);
const notifications = ref({ email: true, sms: false, push: true });
const accepted = ref(false);
const agreed = ref(false);
</script>

<template>
    <Head title="Checkbox — Design Foundation" />

    <h1 class="text-2xl font-semibold mb-10">Checkbox</h1>

    <div class="flex flex-col gap-10">
        <div v-for="section in sections" :id="section.id" :key="section.id" class="flex flex-col gap-3">
            <!-- Section header -->
            <div class="flex items-center justify-between">
                <p class="text-xs font-mono text-tertiary uppercase tracking-widest">{{ section.label }}</p>

                <div class="flex items-center gap-0.5 rounded-md border border-border p-0.5 text-xs">
                    <button
                        class="px-2.5 py-1 rounded transition-colors"
                        :class="
                            views[section.id] === 'preview' ? 'bg-surface text-primary' : 'text-tertiary hover:text-secondary'
                        "
                        @click="views[section.id] = 'preview'"
                    >
                        Preview
                    </button>
                    <button
                        class="px-2.5 py-1 rounded transition-colors"
                        :class="
                            views[section.id] === 'code' ? 'bg-surface text-primary' : 'text-tertiary hover:text-secondary'
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
                class="rounded-lg border border-border p-6 h-[300px] overflow-y-auto"
                style="background: #f8f8f8"
            >
                <div v-if="section.id === 'states'" class="flex items-center gap-4">
                    <Checkbox v-model="stateUnchecked" />
                    <Checkbox v-model="stateChecked" />
                </div>

                <div v-else-if="section.id === 'multiple'" class="flex flex-col gap-3">
                    <Label class="flex items-center gap-2">
                        <Checkbox v-model="notifications.email" />
                        <span>Email notifications</span>
                    </Label>
                    <Label class="flex items-center gap-2">
                        <Checkbox v-model="notifications.sms" />
                        <span>SMS notifications</span>
                    </Label>
                    <Label class="flex items-center gap-2">
                        <Checkbox v-model="notifications.push" />
                        <span>Push notifications</span>
                    </Label>
                </div>

                <div v-else-if="section.id === 'disabled'" class="flex flex-col gap-3">
                    <Label class="flex items-center gap-2">
                        <Checkbox :model-value="false" disabled />
                        <span>Disabled unchecked</span>
                    </Label>
                    <Label class="flex items-center gap-2">
                        <Checkbox :model-value="true" disabled />
                        <span>Disabled checked</span>
                    </Label>
                </div>

                <div v-else-if="section.id === 'reactivity'" class="flex flex-col gap-3">
                    <Label class="flex items-center gap-2">
                        <Checkbox v-model="accepted" />
                        <span>I accept the terms and conditions</span>
                    </Label>
                    <p class="text-sm text-secondary">Accepted: {{ accepted }}</p>
                </div>

                <div v-else-if="section.id === 'label'" class="flex flex-col gap-3">
                    <Label class="flex items-center gap-2">
                        <Checkbox v-model="agreed" />
                        <span>I agree to the terms</span>
                    </Label>
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
