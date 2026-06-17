<script setup lang="ts">
import { ref } from 'vue';
import { Form } from '@inertiajs/vue3';
import SomeController from '@/actions/.../SomeController';
import { Button } from '@/components/ui/button';
import { Dialog } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

const open = ref(false);
</script>

<template>
  <Button @click="open = true">Open form</Button>

  <!-- Form wraps Dialog so errors/processing are accessible in #footer -->
  <Form v-bind="SomeController.store.form()" v-slot="{ errors, processing }">
    <Dialog
      v-model:open="open"
      title="Create item"
      description="Fill in the details below."
    >
      <div class="grid gap-2">
        <Label for="name">Name</Label>
        <Input id="name" name="name" placeholder="Item name" />
        <InputError :message="errors.name" />
      </div>

      <template #footer>
        <Button variant="secondary" @click="open = false">Cancel</Button>
        <Button type="submit" :disabled="processing">Save</Button>
      </template>
    </Dialog>
  </Form>
</template>
