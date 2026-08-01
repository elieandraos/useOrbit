<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import confirmationCode from './snippets/confirmation.md?raw';
import contentOnlyCode from './snippets/content-only.md?raw';
import formDialogCode from './snippets/form-dialog.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'confirmation', label: 'Confirmation dialog' },
    { id: 'form-dialog', label: 'Form submission dialog' },
    { id: 'content-only', label: 'Content-only dialog' },
];

const views = ref<Record<string, ViewMode>>(
    Object.fromEntries(sections.map((s) => [s.id, 'preview'])),
);

const codeSnippets: Record<string, string> = {
    confirmation: confirmationCode,
    'form-dialog': formDialogCode,
    'content-only': contentOnlyCode,
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

const confirmOpen = ref(false);
const formOpen = ref(false);
const contentOpen = ref(false);
const formName = ref('');
</script>

<template>
    <Head title="Dialog — Design Foundation" />

    <h1 class="mb-10 text-2xl font-semibold">Dialog</h1>

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
                class="relative flex h-[300px] items-center justify-center overflow-hidden rounded-lg border border-border p-6"
                style="background: #f8f8f8"
            >
                <!-- Confirmation dialog -->
                <div v-if="section.id === 'confirmation'">
                    <Button variant="destructive" @click="confirmOpen = true"
                        >Delete record</Button
                    >

                    <Dialog
                        v-model:open="confirmOpen"
                        title="Delete record?"
                        description="This action cannot be undone. The record will be permanently removed."
                    >
                        <template #footer>
                            <Button
                                variant="secondary"
                                @click="confirmOpen = false"
                                >Cancel</Button
                            >
                            <Button
                                variant="destructive"
                                @click="confirmOpen = false"
                                >Delete</Button
                            >
                        </template>
                    </Dialog>
                </div>

                <!-- Form dialog -->
                <div v-else-if="section.id === 'form-dialog'">
                    <Button @click="formOpen = true">Create item</Button>

                    <Dialog
                        v-model:open="formOpen"
                        title="Create item"
                        description="Fill in the details below."
                    >
                        <div class="grid gap-2">
                            <Label for="df-name">Name</Label>
                            <Input
                                id="df-name"
                                v-model="formName"
                                placeholder="Item name"
                            />
                        </div>

                        <template #footer>
                            <Button
                                variant="secondary"
                                @click="formOpen = false"
                                >Cancel</Button
                            >
                            <Button @click="formOpen = false">Save</Button>
                        </template>
                    </Dialog>
                </div>

                <!-- Content-only dialog -->
                <div v-else-if="section.id === 'content-only'">
                    <Button variant="secondary" @click="contentOpen = true"
                        >Preview</Button
                    >

                    <Dialog v-model:open="contentOpen">
                        <div
                            class="space-y-3 text-sm leading-relaxed text-secondary"
                        >
                            <p>
                                This dialog has no title or description — the
                                header block is omitted entirely. Useful for
                                image previews, rich content panels, or any
                                modal where the content speaks for itself.
                            </p>
                            <p>Backdrop click and Escape still close it.</p>
                        </div>
                    </Dialog>
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
