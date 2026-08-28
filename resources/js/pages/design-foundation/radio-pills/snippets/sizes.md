```vue
<script setup lang="ts">
import { ref } from 'vue';
import RadioPills from '@/components/ui/radio-pills/RadioPills.vue';

const sm = ref('Female');
const md = ref('Female');
const options = ['Female', 'Male', 'Non-binary', 'Prefer not to say'];
</script>

<template>
    <div class="flex flex-col gap-4">
        <RadioPills v-model="sm" :options="options" size="sm" />
        <RadioPills v-model="md" :options="options" size="md" />
    </div>
</template>
```
