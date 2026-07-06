<script setup lang="ts">
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
</script>

<template>
    <FormField label="Email address" for="email" error="This email is already taken.">
        <Input id="email" model-value="john@example.com" />
    </FormField>
</template>