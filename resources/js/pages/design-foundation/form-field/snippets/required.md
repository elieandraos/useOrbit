<script setup lang="ts">
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
</script>

<template>
    <FormField label="Full name" for="name" required>
        <Input id="name" placeholder="John Doe" />
    </FormField>
</template>