```vue
<script setup lang="ts">
import { ref } from 'vue';
import RangeSlider from '@/components/ui/range-slider/RangeSlider.vue';

const priceRange = ref<[number, number]>([20, 80]);
</script>

<template>
    <div class="flex flex-col gap-2">
        <p class="text-sm text-secondary">
            ${{ priceRange[0] }} – ${{ priceRange[1] }}
        </p>
        <RangeSlider v-model="priceRange" :min="0" :max="100" :step="5" />
    </div>
</template>
```
