<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import { Shield, ChevronRight, Plus } from '@lucide/vue';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import { Card, CardHeader, CardTitle, CardAction, CardContent } from '@/components/ui/card';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import enrollmentCode from './snippets/enrollment.md?raw';
import emergencyContactCode from './snippets/emergency-contact.md?raw';
import policiesCode from './snippets/policies.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'enrollment', label: 'Enrollment' },
    { id: 'emergency-contact', label: 'Emergency contact' },
    { id: 'policies', label: 'Policies' },
];

const views = ref<Record<string, ViewMode>>(Object.fromEntries(sections.map((s) => [s.id, 'preview'])));

const codeSnippets: Record<string, string> = {
    enrollment: enrollmentCode,
    'emergency-contact': emergencyContactCode,
    policies: policiesCode,
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

const policies = [
    { id: 'POL-2042', line: 'Medicare Advantage', carrier: 'Humana', premium: '$184.20 / mo', status: 'Active' },
    { id: 'POL-2043', line: 'Auto · Full coverage', carrier: 'Progressive', premium: '$1,840 / yr', status: 'Active' },
    { id: 'POL-2044', line: 'Homeowners', carrier: 'Travelers', premium: '$1,205 / yr', status: 'Renewing' },
];

function statusTone(status: string): 'success' | 'warning' | 'neutral' {
    if (status === 'Active') return 'success';
    if (status === 'Renewing') return 'warning';
    return 'neutral';
}
</script>

<template>
    <Head title="Card — Design Foundation" />

    <h1 class="text-2xl font-semibold mb-10">Card</h1>

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
                <!-- Enrollment -->
                <div v-if="section.id === 'enrollment'" class="max-w-sm">
                    <Card>
                        <CardHeader>
                            <CardTitle>Enrollment</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                                <div class="flex flex-col gap-1">
                                    <p class="text-[10.5px] font-mono font-semibold text-tertiary uppercase tracking-[0.06em]">Enrolled</p>
                                    <p class="text-[13.5px] text-primary">Feb 8, 2024</p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <p class="text-[10.5px] font-mono font-semibold text-tertiary uppercase tracking-[0.06em]">Lead source</p>
                                    <p class="text-[13.5px] text-primary">Referral</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Emergency contact -->
                <div v-else-if="section.id === 'emergency-contact'" class="max-w-sm">
                    <Card>
                        <CardHeader>
                            <CardTitle>Emergency contact</CardTitle>
                            <CardAction>
                                <Badge tone="success" dot>On file</Badge>
                            </CardAction>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-3">
                                <Avatar name="David Hartwell" size="md" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-[13.5px] font-semibold text-primary leading-none">David Hartwell</p>
                                    <p class="text-xs text-secondary mt-1">
                                        Spouse · <span class="font-mono">(415) 555-0144</span>
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Policies -->
                <div v-else-if="section.id === 'policies'" class="max-w-sm">
                    <Card class="!py-0 !gap-0">
                        <CardHeader class="border-b border-border px-6 py-[14px]">
                            <CardTitle>Policies</CardTitle>
                            <CardAction>
                                <Button variant="ghost" size="sm">
                                    <template #leading><Plus /></template>
                                    Add policy
                                </Button>
                            </CardAction>
                        </CardHeader>
                        <CardContent class="!p-0">
                            <div
                                v-for="policy in policies"
                                :key="policy.id"
                                class="flex items-center gap-3.5 px-6 py-3 border-b border-border-subtle last:border-b-0 hover:bg-sunken transition-colors cursor-pointer"
                            >
                                <div class="flex size-8 items-center justify-center rounded-[10px] bg-accent-bg text-accent shrink-0">
                                    <Shield class="size-4" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-[13.5px] font-semibold text-primary">{{ policy.line }}</span>
                                        <Badge :tone="statusTone(policy.status)" dot>{{ policy.status }}</Badge>
                                    </div>
                                    <p class="text-[11.5px] font-mono text-tertiary mt-0.5">{{ policy.id }} · {{ policy.carrier }}</p>
                                </div>
                                <p class="text-[13px] font-mono font-medium text-primary shrink-0">{{ policy.premium }}</p>
                                <ChevronRight class="size-4 text-tertiary shrink-0" />
                            </div>
                        </CardContent>
                    </Card>
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
