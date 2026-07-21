<script setup lang="ts">
import { Clock, Download, Trash2, X } from '@lucide/vue';
import { computed } from 'vue';
import Badge from '@/components/ui/badge/Badge.vue';
import { Spinner } from '@/components/ui/spinner';
import type { DocumentListItem, DocumentRowItem } from './document';

const props = defineProps<{
    item: DocumentListItem;
    hasActiveUploads?: boolean;
}>();

const emit = defineEmits<{
    cancel: [id: string];
    dismiss: [id: string];
    delete: [document: DocumentRowItem];
}>();

const FILE_KIND_STYLES: Record<string, string> = {
    PDF: 'bg-red-600',
    DOC: 'bg-blue-700',
    XLS: 'bg-emerald-600',
    IMG: 'bg-purple-500',
    OTHER: 'bg-zinc-500',
};

function fileKind(filename: string): keyof typeof FILE_KIND_STYLES {
    const extension = filename.split('.').pop()?.toLowerCase() ?? '';

    if (extension === 'pdf') {
        return 'PDF';
    }

    if (['doc', 'docx'].includes(extension)) {
        return 'DOC';
    }

    if (['xls', 'xlsx'].includes(extension)) {
        return 'XLS';
    }

    if (['jpg', 'jpeg', 'png'].includes(extension)) {
        return 'IMG';
    }

    return 'OTHER';
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    const units = ['KB', 'MB', 'GB'];
    let value = bytes / 1024;
    let unitIndex = 0;

    while (value >= 1024 && unitIndex < units.length - 1) {
        value /= 1024;
        unitIndex++;
    }

    return `${value.toFixed(value < 10 ? 1 : 0)} ${units[unitIndex]}`;
}

const filename = computed(() =>
    props.item.kind === 'upload'
        ? props.item.name
        : props.item.original_filename,
);

const kind = computed(() => fileKind(filename.value));
</script>

<template>
    <!-- In-flight upload: progress -->
    <div
        v-if="item.kind === 'upload' && item.status === 'uploading'"
        class="flex items-center gap-3.5 rounded-md border border-border bg-surface px-3.5 py-3"
    >
        <span
            class="flex size-[38px] shrink-0 items-center justify-center rounded-md border-2 border-accent-ring"
        >
            <Spinner class="size-[18px] text-accent" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[13.5px] font-semibold text-primary">
                {{ item.name }}
            </p>
            <div class="mt-1.5 flex items-center gap-2">
                <div
                    class="h-1 max-w-[220px] flex-1 overflow-hidden rounded-full bg-sunken"
                >
                    <div
                        class="h-full rounded-full bg-accent"
                        :style="{ width: `${item.progress}%` }"
                    />
                </div>
                <span class="font-mono text-[11.5px] text-tertiary"
                    >Uploading… {{ item.progress }}%</span
                >
            </div>
        </div>
        <button
            type="button"
            title="Cancel upload"
            class="shrink-0 p-1 text-tertiary hover:text-primary"
            @click="emit('cancel', item.id)"
        >
            <X class="size-4" />
        </button>
    </div>

    <!-- In-flight upload: rejected by the server -->
    <div
        v-else-if="item.kind === 'upload' && item.status === 'error'"
        class="flex items-center gap-3.5 rounded-md border border-danger bg-danger-bg px-3.5 py-3"
    >
        <span
            class="flex size-[38px] shrink-0 items-center justify-center rounded-md bg-danger text-white"
        >
            <X class="size-4" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[13.5px] font-semibold text-primary">
                {{ item.name }}
            </p>
            <p class="mt-1 text-[11.5px] text-danger">
                {{ item.errorMessage ?? 'Upload failed' }}
            </p>
        </div>
        <button
            type="button"
            title="Dismiss"
            class="shrink-0 p-1 text-danger"
            @click="emit('dismiss', item.id)"
        >
            <X class="size-3.5" />
        </button>
    </div>

    <!-- Persisted document: pending or failed -->
    <div
        v-else-if="
            item.kind === 'document' &&
            (item.status === 'pending' || item.status === 'failed')
        "
        class="flex items-center gap-3.5 rounded-md border px-3.5 py-3"
        :class="
            item.status === 'failed'
                ? 'border-danger bg-danger-bg'
                : 'border-border bg-surface'
        "
    >
        <span
            class="flex size-[38px] shrink-0 items-center justify-center rounded-md"
            :class="
                item.status === 'failed'
                    ? 'bg-danger text-white'
                    : 'bg-sunken text-tertiary'
            "
        >
            <X v-if="item.status === 'failed'" class="size-4" />
            <Clock v-else class="size-4" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[13.5px] font-semibold text-primary">
                {{ item.original_filename }}
            </p>
            <p
                class="mt-1 text-[11.5px]"
                :class="
                    item.status === 'failed'
                        ? 'text-danger'
                        : 'font-mono text-tertiary'
                "
            >
                <template v-if="item.status === 'failed'">
                    {{ item.error_message ?? 'Upload failed' }}
                </template>
                <template v-else>
                    {{ formatBytes(item.size_in_bytes) }} ·
                    {{ item.uploaded_by_name }} ·
                    {{
                        hasActiveUploads
                            ? 'waiting for other uploads…'
                            : 'finalizing…'
                    }}
                </template>
            </p>
        </div>
        <Badge v-if="item.status === 'pending'" tone="warning" dot
            >Processing</Badge
        >
        <Badge v-else tone="danger" dot>Failed</Badge>
        <button
            v-if="item.can_delete"
            type="button"
            title="Delete"
            class="shrink-0 p-1 text-tertiary hover:text-danger"
            @click="emit('delete', item as DocumentRowItem)"
        >
            <Trash2 class="size-3.5" />
        </button>
    </div>

    <!-- Persisted document: completed -->
    <div
        v-else-if="item.kind === 'document' && item.status === 'completed'"
        class="flex items-center gap-3.5 rounded-md border border-border bg-surface px-3.5 py-3"
    >
        <span
            class="flex size-[38px] shrink-0 items-center justify-center rounded-md font-mono text-[10px] font-bold tracking-wide text-white"
            :class="FILE_KIND_STYLES[kind]"
            >{{ kind }}</span
        >
        <div class="min-w-0 flex-1">
            <p class="truncate text-[13.5px] font-semibold text-primary">
                {{ item.original_filename }}
            </p>
            <div
                class="mt-1 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 font-mono text-[11.5px] text-tertiary"
            >
                <span class="whitespace-nowrap">{{
                    formatBytes(item.size_in_bytes)
                }}</span>
                <span>·</span>
                <span class="whitespace-nowrap">{{
                    item.uploaded_by_name
                }}</span>
                <span>·</span>
                <span class="whitespace-nowrap">{{ item.created_at }}</span>
            </div>
        </div>
        <a
            v-if="item.download_url"
            :href="item.download_url"
            title="Download"
            class="shrink-0 p-1 text-tertiary hover:text-primary"
        >
            <Download class="size-3.5" />
        </a>
        <button
            v-if="item.can_delete"
            type="button"
            title="Delete"
            class="shrink-0 p-1 text-tertiary hover:text-danger"
            @click="emit('delete', item as DocumentRowItem)"
        >
            <Trash2 class="size-3.5" />
        </button>
    </div>
</template>
