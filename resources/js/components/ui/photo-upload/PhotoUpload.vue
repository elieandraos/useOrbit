<script setup lang="ts">
import { computed } from 'vue';
import { Camera } from '@lucide/vue';
import { getInitials } from '@/composables/useInitials';
import Button from '@/components/ui/button/Button.vue';

const props = withDefaults(
    defineProps<{
        mode?: 'new' | 'edit';
        name?: string;
    }>(),
    { mode: 'new', name: '' },
);

const initials = computed(() => getInitials(props.name));
</script>

<template>
    <div class="flex items-center gap-5 rounded-lg border border-border-subtle bg-sunken px-5 py-4">
        <div
            v-if="mode === 'new'"
            class="flex size-[72px] shrink-0 items-center justify-center rounded-full border-2 border-dashed border-border text-tertiary"
        >
            <Camera class="size-6" />
        </div>
        <div
            v-else
            class="flex size-[72px] shrink-0 items-center justify-center rounded-full bg-accent-bg text-accent text-lg font-semibold"
        >
            {{ initials }}
        </div>

        <div class="flex flex-col gap-2">
            <div>
                <p class="text-sm font-medium text-primary">{{ mode === 'new' ? 'Add a profile photo' : 'Profile photo' }}</p>
                <p class="mt-0.5 text-xs text-tertiary">
                    {{ mode === 'new' ? 'Optional. Square JPG or PNG, at least 256×256px.' : 'Square JPG or PNG, at least 256×256px.' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="secondary" size="sm">{{ mode === 'new' ? 'Upload photo' : 'Change' }}</Button>
                <Button v-if="mode === 'edit'" variant="ghost" size="sm">Remove</Button>
            </div>
        </div>
    </div>
</template>
