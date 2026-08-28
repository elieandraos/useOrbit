<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Drawer } from '@/components/ui/drawer';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import RadioChips from '@/components/ui/radio-chips/RadioChips.vue';
import { Select } from '@/components/ui/select';

const open = ref(false);
const search = ref('');
const gender = ref('Any');
const source = ref('');

function clear() {
    search.value = '';
    gender.value = 'Any';
    source.value = '';
}
</script>

<template>
  <Button @click="open = true">Filters</Button>

  <Drawer v-model:open="open" title="Filters" description="Refine the client list">
    <div class="flex flex-col gap-5">
      <div class="flex flex-col gap-2">
        <Label for="filters-search">Search</Label>
        <Input id="filters-search" v-model="search" placeholder="Name, phone, or email" />
      </div>

      <div class="flex flex-col gap-2">
        <Label>Gender</Label>
        <RadioChips v-model="gender" :options="['Any', 'Female', 'Male', 'Non-binary']" />
      </div>

      <div class="flex flex-col gap-2">
        <Label for="filters-source">Lead source</Label>
        <Select id="filters-source" v-model="source" placeholder="Any source">
          <option value="referral">Referral</option>
          <option value="website">Website</option>
          <option value="partner">Partner</option>
        </Select>
      </div>
    </div>

    <template #footer>
      <Button variant="ghost" @click="clear">Clear filters</Button>
      <div class="flex-1" />
      <Button @click="open = false">Apply filters</Button>
    </template>

  </Drawer>
</template>
