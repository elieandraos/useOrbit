<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
import defaultCode from './snippets/default.md?raw';
import errorCode from './snippets/error.md?raw';
import helperCode from './snippets/helper.md?raw';
import optionalCode from './snippets/optional.md?raw';
import requiredCode from './snippets/required.md?raw';
import successCode from './snippets/success.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'default', label: 'Default' },
    { id: 'required', label: 'Required' },
    { id: 'optional', label: 'Optional' },
    { id: 'helper', label: 'Helper text' },
    { id: 'error', label: 'Error state' },
    { id: 'success', label: 'Success state' },
];

const views = ref<Record<string, ViewMode>>(Object.fromEntries(sections.map((s) => [s.id, 'preview'])));

const codeSnippets: Record<string, string> = {
    default: defaultCode,
    required: requiredCode,
    optional: optionalCode,
    helper: helperCode,
    error: errorCode,
    success: successCode,
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
</script>

<template>
    <Head title="FormField — Design Foundation" />

    <h1 class="text-2xl font-semibold mb-10">FormField</h1>

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
                <div v-if="section.id === 'default'" class="max-w-sm">
                    <FormField label="Email address" for="email">
                        <Input id="email" placeholder="you@example.com" />
                    </FormField>
                </div>

                <div v-else-if="section.id === 'required'" class="max-w-sm">
                    <FormField label="Full name" for="name" required>
                        <Input id="name" placeholder="John Doe" />
                    </FormField>
                </div>

                <div v-else-if="section.id === 'optional'" class="max-w-sm">
                    <FormField label="Company" for="company" optional>
                        <Input id="company" placeholder="Acme Inc." />
                    </FormField>
                </div>

                <div v-else-if="section.id === 'helper'" class="max-w-sm">
                    <FormField label="Username" for="username" helper="Only letters, numbers, and underscores.">
                        <Input id="username" placeholder="john_doe" />
                    </FormField>
                </div>

                <div v-else-if="section.id === 'error'" class="max-w-sm">
                    <FormField label="Email address" for="email-error" error="This email is already taken.">
                        <Input id="email-error" model-value="john@example.com" />
                    </FormField>
                </div>

                <div v-else-if="section.id === 'success'" class="max-w-sm">
                    <FormField label="Email address" for="email-success" success="Verified · last sent Apr 28">
                        <Input id="email-success" model-value="john@example.com" />
                    </FormField>
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