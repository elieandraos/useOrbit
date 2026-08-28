<script setup lang="ts">
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
</script>

<template>
    <FormField label="Company" for="company" optional>
        <Input id="company" placeholder="Acme Inc." />
    </FormField>
</template>
