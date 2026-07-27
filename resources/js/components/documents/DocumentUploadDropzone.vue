<script setup lang="ts">
import { Upload, X } from '@lucide/vue';
import { computed, ref, useId } from 'vue';
import type { DocumentUploadConfig } from '@/types/document';

const props = defineProps<{
    config: DocumentUploadConfig;
}>();

const emit = defineEmits<{
    files: [files: File[]];
}>();

const inputId = useId();
const dragging = ref(false);
const rejection = ref<{ name: string; reason: string } | null>(null);

let rejectionTimeout: ReturnType<typeof setTimeout> | undefined;

const maxSizeMb = computed(() =>
    Math.round(props.config.max_size_bytes / (1024 * 1024)),
);

const acceptAttribute = computed(() =>
    props.config.allowed_extensions
        .map((extension) => `.${extension}`)
        .join(','),
);

const allowedExtensionsLabel = computed(() =>
    props.config.allowed_extensions
        .map((extension) => extension.toUpperCase())
        .join(', '),
);

function extensionOf(file: File): string {
    return file.name.split('.').pop()?.toLowerCase() ?? '';
}

function reject(name: string, reason: string): void {
    clearTimeout(rejectionTimeout);
    rejection.value = { name, reason };
    rejectionTimeout = setTimeout(() => {
        rejection.value = null;
    }, 4000);
}

function dismissRejection(): void {
    clearTimeout(rejectionTimeout);
    rejection.value = null;
}

function handleFiles(fileList: FileList | null): void {
    if (!fileList || fileList.length === 0) {
        return;
    }

    const files = Array.from(fileList);

    if (files.length > props.config.max_files_per_batch) {
        reject(
            `${files.length} files`,
            `Max ${props.config.max_files_per_batch} files per upload`,
        );

        return;
    }

    const accepted: File[] = [];

    for (const file of files) {
        if (!props.config.allowed_extensions.includes(extensionOf(file))) {
            reject(
                file.name,
                `Unsupported file type — try ${allowedExtensionsLabel.value}`,
            );

            continue;
        }

        if (file.size > props.config.max_size_bytes) {
            reject(
                file.name,
                `File too large — max ${maxSizeMb.value} MB per file`,
            );

            continue;
        }

        accepted.push(file);
    }

    if (accepted.length > 0) {
        emit('files', accepted);
    }
}

function onDrop(event: DragEvent): void {
    dragging.value = false;
    handleFiles(event.dataTransfer?.files ?? null);
}

function onInputChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    handleFiles(target.files);
    target.value = '';
}

function onLabelClick(event: MouseEvent): void {
    if (rejection.value) {
        event.preventDefault();
        dismissRejection();
    }
}
</script>

<template>
    <label
        :for="inputId"
        class="relative m-[18px] flex cursor-pointer items-center justify-center gap-3.5 rounded-md border-[1.5px] border-dashed px-[18px] py-[22px] transition-colors"
        :class="
            rejection
                ? 'border-danger bg-danger-bg'
                : dragging
                  ? 'border-accent bg-accent-bg'
                  : 'border-border-strong bg-sunken'
        "
        @dragover.prevent="dragging = true"
        @dragleave.prevent="dragging = false"
        @drop.prevent="onDrop"
        @click="onLabelClick"
    >
        <input
            :id="inputId"
            type="file"
            multiple
            class="hidden"
            :accept="acceptAttribute"
            @change="onInputChange"
        />

        <button
            v-if="rejection"
            type="button"
            title="Dismiss"
            class="absolute top-1/2 right-3 shrink-0 -translate-y-1/2 p-1 text-danger hover:text-danger/80"
            @click.stop.prevent="dismissRejection"
        >
            <X class="size-4" />
        </button>

        <div
            class="flex size-9 shrink-0 items-center justify-center rounded-md"
            :class="
                rejection
                    ? 'border border-danger bg-surface text-danger'
                    : dragging
                      ? 'border border-accent bg-surface text-accent'
                      : 'bg-accent-bg text-accent'
            "
        >
            <X v-if="rejection" class="size-[18px]" />
            <Upload v-else class="size-[18px]" />
        </div>

        <div>
            <template v-if="rejection">
                <p class="text-[13.5px] font-semibold text-danger">
                    "{{ rejection.name }}" couldn't be added
                </p>
                <p class="mt-0.5 text-xs text-danger opacity-80">
                    {{ rejection.reason }}
                </p>
            </template>
            <template v-else>
                <p class="text-[13.5px] font-semibold text-primary">
                    <template v-if="dragging">Drop to upload</template>
                    <template v-else
                        >Drag &amp; drop files here — or
                        <span class="text-accent">browse</span></template
                    >
                </p>
                <p class="mt-0.5 text-xs text-tertiary">
                    {{ allowedExtensionsLabel }} · Max {{ maxSizeMb }} MB per
                    file
                </p>
            </template>
        </div>
    </label>
</template>
