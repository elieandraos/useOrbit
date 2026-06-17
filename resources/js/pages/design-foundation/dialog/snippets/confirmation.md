<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';

const open = ref(false);
</script>

<template>
  <Button @click="open = true">Delete record</Button>

  <Dialog
    v-model:open="open"
    title="Delete record?"
    description="This action cannot be undone. The record will be permanently removed."
  >
    <template #footer>
      <Button variant="secondary" @click="open = false">Cancel</Button>
      <Button variant="destructive" @click="open = false">Delete</Button>
    </template>
  </Dialog>
</template>
