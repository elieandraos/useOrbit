<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { Tab, Tabs } from '@/components/ui/tabs';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Appearance settings',
                href: editAppearance(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Appearance settings" />

    <div>
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <Tabs>
            <Tab :href="editProfile()">Profile</Tab>
            <Tab :href="editSecurity()">Security</Tab>
            <Tab :href="editAppearance()">Appearance</Tab>
        </Tabs>

        <section class="max-w-xl space-y-12 py-8">
            <h1 class="sr-only">Appearance settings</h1>

            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Appearance settings"
                    description="Update the appearance settings for your account"
                />
                <AppearanceTabs />
            </div>
        </section>
    </div>
</template>
