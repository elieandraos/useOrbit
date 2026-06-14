<script setup lang="ts">
import Textarea from '@/components/ui/textarea/Textarea.vue';
import Label from '@/components/ui/label/Label.vue';
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <Label for="bio">Bio</Label>
        <Textarea id="bio" placeholder="Tell us about yourself..." />
    </div>
</template>
