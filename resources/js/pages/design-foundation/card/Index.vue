<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Shield, ChevronRight, Plus } from '@lucide/vue';
import { codeToHtml } from 'shiki';
import { onMounted, ref } from 'vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import {
    Card,
    CardHeader,
    CardTitle,
    CardAction,
    CardContent,
} from '@/components/ui/card';
import DesignFoundationLayout from '@/layouts/DesignFoundationLayout.vue';
import emergencyContactCode from './snippets/emergency-contact.md?raw';
import enrollmentCode from './snippets/enrollment.md?raw';
import policiesCode from './snippets/policies.md?raw';

defineOptions({ layout: DesignFoundationLayout });

type ViewMode = 'preview' | 'code';

const sections = [
    { id: 'enrollment', label: 'Enrollment' },
    { id: 'emergency-contact', label: 'Emergency contact' },
    { id: 'policies', label: 'Policies' },
];

const views = ref<Record<string, ViewMode>>(
    Object.fromEntries(sections.map((s) => [s.id, 'preview'])),
);

const codeSnippets: Record<string, string> = {
    enrollment: enrollmentCode,
    'emergency-contact': emergencyContactCode,
    policies: policiesCode,
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

const policies = [
    {
        id: 'POL-2042',
        line: 'Medicare Advantage',
        carrier: 'Humana',
        premium: '$184.20 / mo',
        status: 'Active',
    },
    {
        id: 'POL-2043',
        line: 'Auto · Full coverage',
        carrier: 'Progressive',
        premium: '$1,840 / yr',
        status: 'Active',
    },
    {
        id: 'POL-2044',
        line: 'Homeowners',
        carrier: 'Travelers',
        premium: '$1,205 / yr',
        status: 'Renewing',
    },
];

function statusTone(status: string): 'success' | 'warning' | 'neutral' {
    if (status === 'Active') {
        return 'success';
    }

    if (status === 'Renewing') {
        return 'warning';
    }

    return 'neutral';
}
</script>

<template>
    <Head title="Card — Design Foundation" />

    <h1 class="mb-10 text-2xl font-semibold">Card</h1>

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
                <!-- Enrollment -->
                <div v-if="section.id === 'enrollment'" class="max-w-sm">
                    <Card>
                        <CardHeader>
                            <CardTitle>Enrollment</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-2 gap-x-3.5 gap-y-4">
                                <div class="flex flex-col gap-1">
                                    <p
                                        class="font-mono text-[10.5px] font-semibold tracking-[0.06em] text-tertiary uppercase"
                                    >
                                        Enrolled
                                    </p>
                                    <p class="text-[13.5px] text-primary">
                                        Feb 8, 2024
                                    </p>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <p
                                        class="font-mono text-[10.5px] font-semibold tracking-[0.06em] text-tertiary uppercase"
                                    >
                                        Lead source
                                    </p>
                                    <p class="text-[13.5px] text-primary">
                                        Referral
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Emergency contact -->
                <div
                    v-else-if="section.id === 'emergency-contact'"
                    class="max-w-sm"
                >
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
                                <div class="min-w-0 flex-1">
                                    <p
                                        class="text-[13.5px] leading-none font-semibold text-primary"
                                    >
                                        David Hartwell
                                    </p>
                                    <p class="mt-1 text-xs text-secondary">
                                        Spouse ·
                                        <span class="font-mono"
                                            >(415) 555-0144</span
                                        >
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Policies -->
                <div v-else-if="section.id === 'policies'" class="max-w-sm">
                    <Card>
                        <CardHeader bordered>
                            <CardTitle>Policies</CardTitle>
                            <CardAction>
                                <Button variant="ghost" size="sm">
                                    <template #leading><Plus /></template>
                                    Add policy
                                </Button>
                            </CardAction>
                        </CardHeader>
                        <CardContent class="p-0">
                            <div
                                v-for="policy in policies"
                                :key="policy.id"
                                class="flex cursor-pointer items-center gap-3.5 border-b border-border-subtle px-6 py-3 transition-colors last:border-b-0 hover:bg-sunken"
                            >
                                <div
                                    class="flex size-8 shrink-0 items-center justify-center rounded-[10px] bg-accent-bg text-accent"
                                >
                                    <Shield class="size-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-[13.5px] font-semibold text-primary"
                                            >{{ policy.line }}</span
                                        >
                                        <Badge
                                            :tone="statusTone(policy.status)"
                                            dot
                                            >{{ policy.status }}</Badge
                                        >
                                    </div>
                                    <p
                                        class="mt-0.5 font-mono text-[11.5px] text-tertiary"
                                    >
                                        {{ policy.id }} · {{ policy.carrier }}
                                    </p>
                                </div>
                                <p
                                    class="shrink-0 font-mono text-[13px] font-medium text-primary"
                                >
                                    {{ policy.premium }}
                                </p>
                                <ChevronRight
                                    class="size-4 shrink-0 text-tertiary"
                                />
                            </div>
                        </CardContent>
                    </Card>
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
