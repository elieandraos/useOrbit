<script setup lang="ts">
import { ref } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import Spinner from '@/components/ui/spinner/Spinner.vue';

const isLoading = ref(false);
</script>

<template>
    <Button :disabled="isLoading" @click="isLoading = !isLoading">
        <template v-if="isLoading" #leading>
            <Spinner />
        </template>
        {{ isLoading ? 'Saving...' : 'Save changes' }}
    </Button>
</template>
