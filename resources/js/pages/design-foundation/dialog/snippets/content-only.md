<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';

const open = ref(false);
</script>

<template>
  <Button variant="secondary" @click="open = true">Preview</Button>

  <!-- No title or description — header block is omitted entirely -->
  <Dialog v-model:open="open">
    <img src="/preview.jpg" alt="Preview" class="w-full rounded-lg" />
  </Dialog>
</template>
