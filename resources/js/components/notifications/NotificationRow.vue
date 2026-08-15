<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, inject } from 'vue';
import {
    MEMBER_JOINED,
    MEMBER_REMOVED,
    MEMBER_ROLE_CHANGED,
    notificationTypes,
    RESOURCE_ARCHIVED,
    RESOURCE_MESSAGE,
    RESOURCE_UNARCHIVED,
} from '@/lib/notificationTypes';
import type {
    MemberRemovedMeta,
    MemberRoleChangedMeta,
    NotificationEnvelope,
    NotificationItem,
} from '@/types/notification';

const props = defineProps<{
    notification: NotificationItem;
    dense?: boolean;
}>();

const emit = defineEmits<{
    select: [id: string];
}>();

const closeMenu = inject<() => void>('dropMenuClose', () => {});

const data = computed(
    () => props.notification.data as NotificationEnvelope<unknown>,
);
const meta = computed(() => notificationTypes[data.value.action]);
const href = computed(
    () => meta.value?.resolveUrl(props.notification.data) ?? '#',
);
const isUnread = computed(() => props.notification.read_at === null);
const isResourceMessage = computed(
    () => data.value.action === RESOURCE_MESSAGE,
);
const isResourceEvent = computed(
    () =>
        data.value.action === RESOURCE_ARCHIVED ||
        data.value.action === RESOURCE_UNARCHIVED,
);
const resourceEventVerb = computed(() =>
    data.value.action === RESOURCE_ARCHIVED ? 'archived' : 'unarchived',
);
const isMemberJoined = computed(() => data.value.action === MEMBER_JOINED);
const isMemberRoleChanged = computed(
    () => data.value.action === MEMBER_ROLE_CHANGED,
);
const isMemberRemoved = computed(() => data.value.action === MEMBER_REMOVED);
const roleChangedMeta = computed(
    () => data.value.meta as MemberRoleChangedMeta,
);
const removedMeta = computed(() => data.value.meta as MemberRemovedMeta);

function handleClick(): void {
    if (isUnread.value) {
        emit('select', props.notification.id);
    }

    closeMenu();
}
</script>

<template>
    <Link
        :href="href"
        class="flex w-full items-start gap-2.5 text-left transition-colors hover:bg-sunken"
        :class="[
            dense ? 'rounded-[6px] px-2 py-2' : 'px-4 py-3.5',
            isUnread && 'bg-accent-bg',
        ]"
        @click="handleClick"
    >
        <span
            class="flex size-7 shrink-0 items-center justify-center rounded-full bg-accent-bg text-accent"
        >
            <component :is="meta?.icon" class="size-3.5" />
        </span>
        <span class="min-w-0 flex-1">
            <span
                v-if="isResourceMessage"
                class="block text-sm leading-snug"
                :class="isUnread ? 'text-primary' : 'text-secondary'"
            >
                <span class="font-semibold text-primary">{{
                    data.actor?.name ?? 'Someone'
                }}</span>
                notified you about
                <span class="font-semibold text-primary">{{
                    data.subject.name
                }}</span
                >.
            </span>
            <span
                v-else-if="isResourceEvent"
                class="block text-sm leading-snug"
                :class="isUnread ? 'text-primary' : 'text-secondary'"
            >
                <span class="font-semibold text-primary">{{
                    data.actor?.name ?? 'Someone'
                }}</span>
                {{ resourceEventVerb }} {{ data.subject.kind }}
                <span class="font-semibold text-primary">{{
                    data.subject.name
                }}</span
                >.
            </span>
            <span
                v-else-if="isMemberJoined"
                class="block text-sm leading-snug"
                :class="isUnread ? 'text-primary' : 'text-secondary'"
            >
                <span class="font-semibold text-primary">{{
                    data.actor?.name ?? 'Someone'
                }}</span>
                joined
                <span class="font-semibold text-primary">{{
                    data.subject.name
                }}</span
                >.
            </span>
            <span
                v-else-if="isMemberRoleChanged"
                class="block text-sm leading-snug"
                :class="isUnread ? 'text-primary' : 'text-secondary'"
            >
                <span class="font-semibold text-primary">{{
                    data.actor?.name ?? 'Someone'
                }}</span>
                changed
                <span class="font-semibold text-primary">{{
                    roleChangedMeta.member.name
                }}</span
                >'s role from
                <span class="capitalize">{{ roleChangedMeta.from_role }}</span>
                to
                <span class="capitalize">{{ roleChangedMeta.to_role }}</span
                >.
            </span>
            <span
                v-else-if="isMemberRemoved"
                class="block text-sm leading-snug"
                :class="isUnread ? 'text-primary' : 'text-secondary'"
            >
                <span class="font-semibold text-primary">{{
                    data.actor?.name ?? 'Someone'
                }}</span>
                removed
                <span class="font-semibold text-primary">{{
                    removedMeta.member.name
                }}</span>
                from {{ data.subject.name }}.
            </span>
            <span v-else class="block text-xs leading-snug text-tertiary">
                {{ data.summary }}
            </span>
            <span
                v-if="isResourceMessage"
                class="block text-xs leading-snug text-tertiary"
            >
                {{ data.summary }}
            </span>
            <span
                class="mt-0.5 block text-right font-mono text-xs text-tertiary"
            >
                {{ notification.created_at }}
            </span>
        </span>
        <span
            v-if="isUnread"
            class="mt-1.5 size-1.5 shrink-0 rounded-full bg-accent"
        />
    </Link>
</template>
