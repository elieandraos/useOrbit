<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Archive,
    Building2,
    Eye,
    MoreHorizontal,
    Pencil,
    Plus,
} from '@lucide/vue';
import { Avatar } from '@/components/ui/avatar';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Separator } from '@/components/ui/separator';
import { edit as carriersEdit, show as carriersShow } from '@/routes/carriers';
import type { CarrierResource } from './carrier';

defineProps<{
    carrier: CarrierResource;
}>();

const emit = defineEmits<{
    archive: [carrier: CarrierResource];
}>();

function goToCarrier(carrier: CarrierResource) {
    router.visit(carriersShow(carrier.slug).url);
}
</script>

<template>
    <!-- Rendered only below `md` by CarriersTable.vue, in place of a table row -->
    <div
        class="cursor-pointer rounded-lg border border-border bg-surface p-3.5 shadow-card transition-colors active:bg-sunken"
        @click="goToCarrier(carrier)"
    >
        <div class="flex items-start gap-3">
            <Avatar :name="carrier.name" :size="36" />
            <div class="min-w-0 flex-1">
                <div class="truncate text-[14px] font-medium text-primary">
                    {{ carrier.name }}
                </div>
                <div
                    v-if="carrier.website"
                    class="mt-0.5 truncate font-mono text-[11.5px] text-tertiary"
                >
                    {{ carrier.website }}
                </div>
            </div>

            <div @click.stop>
                <DropMenu>
                    <template #trigger>
                        <button
                            class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-secondary transition-colors hover:bg-sunken"
                        >
                            <MoreHorizontal class="size-4" />
                        </button>
                    </template>

                    <DropMenuItem :href="carriersShow(carrier.slug).url">
                        <template #leading><Eye class="size-4" /></template>
                        View carrier
                    </DropMenuItem>
                    <DropMenuItem :href="carriersEdit(carrier.slug).url">
                        <template #leading><Pencil class="size-4" /></template>
                        Edit
                    </DropMenuItem>
                    <DropMenuItem disabled class="pointer-events-none opacity-50">
                        <template #leading><Plus class="size-4" /></template>
                        Add policy
                    </DropMenuItem>
                    <Separator class="my-1" />
                    <DropMenuItem danger @click="emit('archive', carrier)">
                        <template #leading
                            ><Archive class="size-4"
                        /></template>
                        Archive
                    </DropMenuItem>
                </DropMenu>
            </div>
        </div>

        <div
            class="mt-3 flex flex-col gap-1.5 border-t border-border-subtle pt-2.5"
        >
            <div v-if="carrier.branch?.contact_name" class="flex min-w-0 items-center gap-2">
                <span class="truncate text-[13px] text-secondary">
                    {{ carrier.branch.contact_name }}
                    <template v-if="carrier.branch.contact_role"
                        >· {{ carrier.branch.contact_role }}</template
                    >
                </span>
            </div>
            <div v-if="carrier.branch?.city" class="flex min-w-0 items-center gap-2">
                <Building2 class="size-3.5 shrink-0 text-tertiary" />
                <span class="truncate text-[13px] text-secondary">
                    {{ carrier.branch.city }}
                </span>
            </div>
        </div>
    </div>
</template>
