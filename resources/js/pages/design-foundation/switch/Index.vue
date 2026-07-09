<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref, useId } from 'vue';
import Switch from '@/components/ui/switch/Switch.vue';
import SwitchField from '@/components/ui/switch/SwitchField.vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import disabledCode from './snippets/disabled.md?raw';
import labeledRowCode from './snippets/labeled-row.md?raw';
import reactivityCode from './snippets/reactivity.md?raw';
import sizesCode from './snippets/sizes.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'sizes', label: 'Sizes' },
    { id: 'disabled', label: 'Disabled' },
    { id: 'labeled-row', label: 'Labeled row' },
    { id: 'reactivity', label: 'Reactivity' },
];

const views = ref<Record<string, ViewMode>>(
    Object.fromEntries(sections.map((s) => [s.id, 'preview'])),
);

const codeSnippets: Record<string, string> = {
    sizes: sizesCode,
    disabled: disabledCode,
    'labeled-row': labeledRowCode,
    reactivity: reactivityCode,
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

const sizeMd = ref(true);
const sizeLg = ref(true);
const emailNotifications = ref(true);
const autoRenew = ref(false);
const enabled = ref(false);
const twoFactorId = useId();
</script>

<template>
    <Head title="Switch — Design Foundation" />

    <h1 class="mb-10 text-2xl font-semibold">Switch</h1>

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
                class="h-[300px] overflow-y-auto rounded-lg border border-border p-6"
                style="background: #f8f8f8"
            >
                <div
                    v-if="section.id === 'sizes'"
                    class="flex items-center gap-6"
                >
                    <Switch v-model="sizeMd" size="md" />
                    <Switch v-model="sizeLg" size="lg" />
                </div>

                <div
                    v-else-if="section.id === 'disabled'"
                    class="flex items-center gap-6"
                >
                    <Switch :model-value="false" disabled />
                    <Switch :model-value="true" disabled />
                </div>

                <div
                    v-else-if="section.id === 'labeled-row'"
                    class="flex flex-col gap-2.5"
                >
                    <SwitchField
                        label="Email notifications"
                        description="Get a digest every Monday morning."
                        v-slot="{ id }"
                    >
                        <Switch :id="id" v-model="emailNotifications" />
                    </SwitchField>
                    <SwitchField
                        label="Auto-renew policies"
                        description="Trigger renewal flow 30 days before expiry."
                        v-slot="{ id }"
                    >
                        <Switch :id="id" v-model="autoRenew" />
                    </SwitchField>
                </div>

                <div
                    v-else-if="section.id === 'reactivity'"
                    class="flex flex-col gap-3"
                >
                    <div class="flex items-center gap-2">
                        <Switch :id="twoFactorId" v-model="enabled" />
                        <label
                            :for="twoFactorId"
                            class="cursor-pointer text-sm text-primary"
                            >Two-factor authentication</label
                        >
                    </div>
                    <p class="text-sm text-secondary">Enabled: {{ enabled }}</p>
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
