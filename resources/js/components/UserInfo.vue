<script setup lang="ts">
import { Avatar } from '@/components/ui/avatar';
import type { User } from '@/types';

type Props = {
    user: User;
    showEmail?: boolean;
};

const { user, showEmail } = withDefaults(defineProps<Props>(), {
    showEmail: false,
});
</script>

<template>
    <Avatar :name="user.name" :src="user.avatar ?? undefined" />

    <div class="grid flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{
            user.email
        }}</span>
    </div>
</template>
