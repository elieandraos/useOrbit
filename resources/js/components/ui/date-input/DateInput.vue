<script setup lang="ts">
import { computed, ref, watch, watchEffect } from 'vue'
import Select from '@/components/ui/select/Select.vue'

const props = withDefaults(defineProps<{
    modelValue?: string
    name?: string
    startYear?: number
    endYear?: number
    size?: 'sm' | 'md'
}>(), {
    startYear: 1920,
    size: 'md',
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

function parseDate(val?: string) {
    if (!val) return { year: '', month: '', day: '' }
    const [year = '', month = '', day = ''] = val.split('-')
    return { year, month, day }
}

const { year: initYear, month: initMonth, day: initDay } = parseDate(props.modelValue)
const internalYear = ref(initYear)
const internalMonth = ref(initMonth)
const internalDay = ref(initDay)

watch(() => props.modelValue, (val) => {
    const { year, month, day } = parseDate(val)
    const currentEmit =
        internalYear.value && internalMonth.value && internalDay.value
            ? `${internalYear.value}-${internalMonth.value}-${internalDay.value}`
            : ''
    if (val !== currentEmit) {
        internalYear.value = year
        internalMonth.value = month
        internalDay.value = day
    }
})

watch([internalYear, internalMonth, internalDay], ([y, m, d]) => {
    emit('update:modelValue', y && m && d ? `${y}-${m}-${d}` : '')
})

const hiddenValue = computed(() =>
    internalYear.value && internalMonth.value && internalDay.value
        ? `${internalYear.value}-${internalMonth.value}-${internalDay.value}`
        : '',
)

const days = Array.from({ length: 31 }, (_, i) => String(i + 1).padStart(2, '0'))

const months = [
    { label: 'Jan', value: '01' },
    { label: 'Feb', value: '02' },
    { label: 'Mar', value: '03' },
    { label: 'Apr', value: '04' },
    { label: 'May', value: '05' },
    { label: 'Jun', value: '06' },
    { label: 'Jul', value: '07' },
    { label: 'Aug', value: '08' },
    { label: 'Sep', value: '09' },
    { label: 'Oct', value: '10' },
    { label: 'Nov', value: '11' },
    { label: 'Dec', value: '12' },
]

const currentYear = new Date().getFullYear()

watchEffect(() => {
    const end = props.endYear ?? currentYear
    if (end <= props.startYear) {
        console.warn(`[DateInput] endYear (${end}) must be greater than startYear (${props.startYear}).`)
    }
})

const years = computed(() => {
    const end = props.endYear ?? currentYear
    if (end <= props.startYear) return []
    return Array.from({ length: end - props.startYear + 1 }, (_, i) => String(end - i))
})
</script>

<template>
    <div class="flex items-center gap-2">
        <Select v-model="internalDay" placeholder="Day" :size="size">
            <option v-for="day in days" :key="day" :value="day">{{ Number(day) }}</option>
        </Select>
        <Select v-model="internalMonth" placeholder="Month" :size="size">
            <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
        </Select>
        <Select v-model="internalYear" placeholder="Year" :size="size">
            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
        </Select>
        <input v-if="name" type="hidden" :name="name" :value="hiddenValue" />
    </div>
</template>
