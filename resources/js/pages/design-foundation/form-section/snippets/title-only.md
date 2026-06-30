<script setup lang="ts">
import FormSection from '@/components/ui/form-section/FormSection.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
</script>

<template>
    <FormSection title="Personal information">
        <FormField label="Full name" for="name">
            <Input id="name" placeholder="John Doe" />
        </FormField>
    </FormSection>
</template>
