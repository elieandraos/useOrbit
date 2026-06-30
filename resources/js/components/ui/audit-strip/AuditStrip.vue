<script setup lang="ts">
interface Props {
    created: string;
    updated: string;
    by: string;
}

defineProps<Props>();
</script>

<template>
    <div class="bg-sunken border border-border-subtle rounded-md font-mono text-xs text-tertiary flex flex-wrap items-center gap-x-2 gap-y-1 px-[14px] py-[10px]">
        <span>CREATED</span>
        <span class="text-border-strong">·</span>
        <span>{{ created }}</span>
        <span class="text-border-strong">·</span>
        <span>UPDATED</span>
        <span class="text-border-strong">·</span>
        <span>{{ updated }}</span>
        <span class="text-border-strong">·</span>
        <span>BY</span>
        <span class="text-border-strong">·</span>
        <span>{{ by }}</span>
    </div>
</template>
