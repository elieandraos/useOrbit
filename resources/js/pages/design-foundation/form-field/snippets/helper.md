<script setup lang="ts">
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
</script>

<template>
    <FormField label="Username" for="username" helper="Only letters, numbers, and underscores.">
        <Input id="username" placeholder="john_doe" />
    </FormField>
</template>