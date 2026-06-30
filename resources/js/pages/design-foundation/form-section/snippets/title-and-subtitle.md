<script setup lang="ts">
import FormSection from '@/components/ui/form-section/FormSection.vue';
import FormField from '@/components/ui/form-field/FormField.vue';
import Input from '@/components/ui/input/Input.vue';
</script>

<template>
    <FormSection title="Emergency contact" subtitle="Person to reach in case of an emergency.">
        <FormField label="Full name" for="emergency-name">
            <Input id="emergency-name" placeholder="Jane Doe" />
        </FormField>
    </FormSection>
</template>
