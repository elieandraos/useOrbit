```vue
<script setup lang="ts">
import { ref } from 'vue';
import RangeSlider from '@/components/ui/range-slider/RangeSlider.vue';

const ageRange = ref<[number, number]>([26, 72]);
</script>

<template>
    <div class="flex flex-col gap-2">
        <RangeSlider
            v-model="ageRange"
            :min="18"
            :max="90"
            label-suffix=" yrs"
        />
    </div>
</template>
```
