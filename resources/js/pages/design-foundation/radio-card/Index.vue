<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import { RadioCard } from '@/components/ui/radio-card';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import formFieldCode from './snippets/form-field.md?raw';
import reactivityCode from './snippets/reactivity.md?raw';
import roleSelectionCode from './snippets/role-selection.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'reactivity', label: 'Reactivity' },
    { id: 'role-selection', label: 'With description' },
    { id: 'form-field', label: 'Inside a FormField' },
];

const views = ref<Record<string, ViewMode>>(
    Object.fromEntries(sections.map((s) => [s.id, 'preview'])),
);

const codeSnippets: Record<string, string> = {
    reactivity: reactivityCode,
    'role-selection': roleSelectionCode,
    'form-field': formFieldCode,
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

const reactivitySelected = ref('starter');
const roleSelected = ref('member');
const formFieldSelected = ref('member');

const planOptions = [
    { label: 'Starter', value: 'starter' },
    { label: 'Pro', value: 'pro' },
];
const roleOptions = [
    {
        label: 'Admin',
        value: 'admin',
        desc: 'Full access including member management and billing',
    },
    {
        label: 'Member',
        value: 'member',
        desc: "Can view and manage everything, but can't take destructive actions like archiving or deleting",
    },
];
</script>

<template>
    <Head title="RadioCard — Design Foundation" />

    <h1 class="mb-10 text-2xl font-semibold">RadioCard</h1>

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
                    v-if="section.id === 'reactivity'"
                    class="flex flex-col gap-4"
                >
                    <RadioCard
                        v-model="reactivitySelected"
                        :options="planOptions"
                    />
                    <p class="text-sm text-secondary">
                        Selected: {{ reactivitySelected }}
                    </p>
                </div>

                <div v-else-if="section.id === 'role-selection'">
                    <RadioCard v-model="roleSelected" :options="roleOptions" />
                </div>

                <div v-else-if="section.id === 'form-field'">
                    <FormField label="Role" required>
                        <RadioCard
                            v-model="formFieldSelected"
                            name="role"
                            :options="roleOptions"
                        />
                    </FormField>
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
