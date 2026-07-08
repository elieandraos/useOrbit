<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Drawer } from '@/components/ui/drawer';

const open = ref(false);
</script>

<template>
  <Button variant="secondary" @click="open = true">Open panel</Button>

  <Drawer v-model:open="open">
    <p class="text-sm leading-relaxed text-secondary">
      This drawer has no title or description — the header row still shows the close
      button, but the title block is omitted entirely. Useful for previews or any
      panel where the content speaks for itself.
    </p>

    <template #footer>
      <Button variant="secondary" @click="open = false">Close</Button>
    </template>
  </Drawer>
</template>
