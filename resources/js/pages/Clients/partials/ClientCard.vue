<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Archive,
    ArchiveRestore,
    Building2,
    Calendar,
    Eye,
    MoreHorizontal,
    Pencil,
    Phone,
    Trash2,
    Users,
} from '@lucide/vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import { DropMenu, DropMenuItem } from '@/components/ui/drop-menu';
import { Separator } from '@/components/ui/separator';
import { useAuth } from '@/composables/useAuth';
import { edit as clientsEdit, show as clientsShow } from '@/routes/clients';
import type { ClientResource } from './client';

defineProps<{
    client: ClientResource;
}>();

const emit = defineEmits<{
    archive: [client: ClientResource];
    unarchive: [client: ClientResource];
    delete: [client: ClientResource];
}>();

const { isPrivileged } = useAuth();

function goToClient(client: ClientResource) {
    router.visit(clientsShow(client.slug).url);
}
</script>

<template>
    <!-- Rendered only below `md` by ClientsTable.vue, in place of a table row -->
    <div
        class="cursor-pointer rounded-lg border border-border bg-surface p-3.5 shadow-card transition-colors active:bg-sunken"
        @click="goToClient(client)"
    >
        <div class="flex items-start gap-3">
            <Avatar :name="client.full_name" :size="36" />
            <div class="min-w-0 flex-1">
                <div class="truncate text-[14px] font-medium text-primary">
                    {{ client.full_name }}
                </div>
                <div
                    v-if="client.client_type === 'individual'"
                    class="mt-0.5 font-mono text-[11.5px] text-tertiary"
                >
                    {{ client.age }} yrs
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

                    <DropMenuItem :href="clientsShow(client.slug).url">
                        <template #leading><Eye class="size-4" /></template>
                        View
                    </DropMenuItem>
                    <DropMenuItem :href="clientsEdit(client.slug).url">
                        <template #leading><Pencil class="size-4" /></template>
                        Edit
                    </DropMenuItem>
                    <Separator v-if="isPrivileged" class="my-1" />
                    <template v-if="client.status === 'archived'">
                        <DropMenuItem
                            v-if="isPrivileged"
                            @click="emit('unarchive', client)"
                        >
                            <template #leading
                                ><ArchiveRestore class="size-4"
                            /></template>
                            Unarchive
                        </DropMenuItem>
                        <DropMenuItem
                            v-if="isPrivileged"
                            danger
                            @click="emit('delete', client)"
                        >
                            <template #leading
                                ><Trash2 class="size-4"
                            /></template>
                            Delete permanently
                        </DropMenuItem>
                    </template>
                    <DropMenuItem
                        v-else-if="isPrivileged"
                        danger
                        @click="emit('archive', client)"
                    >
                        <template #leading><Archive class="size-4" /></template>
                        Archive
                    </DropMenuItem>
                </DropMenu>
            </div>
        </div>

        <div
            class="mt-3 flex flex-col gap-1.5 border-t border-border-subtle pt-2.5"
        >
            <div class="flex min-w-0 items-center gap-2">
                <Phone class="size-3.5 shrink-0 text-tertiary" />
                <span class="truncate font-mono text-[12.5px] text-secondary">
                    {{ client.phone }}
                </span>
            </div>
            <div class="flex min-w-0 items-center gap-2">
                <Building2
                    v-if="client.client_type === 'company'"
                    class="size-3.5 shrink-0 text-tertiary"
                />
                <Users v-else class="size-3.5 shrink-0 text-tertiary" />
                <Badge
                    :tone="
                        client.client_type === 'company' ? 'accent' : 'neutral'
                    "
                    dot
                >
                    {{ client.client_type_label }}
                </Badge>
            </div>
            <div class="flex min-w-0 items-center gap-2">
                <Calendar class="size-3.5 shrink-0 text-tertiary" />
                <span class="truncate text-[13px] text-secondary">
                    Enrolled {{ client.enrollment_date_formatted }}
                </span>
            </div>
        </div>
    </div>
</template>
