<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tab, Tabs } from '@/components/ui/tabs';
import { useAuth } from '@/composables/useAuth';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editOrganization } from '@/routes/organization';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: editProfile(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const { isOwner } = useAuth();
</script>

<template>
    <Head title="Profile settings" />

    <div>
        <Heading
            title="Settings"
            description="Manage your profile and account settings"
        />

        <Tabs>
            <Tab :href="editProfile()">Profile</Tab>
            <Tab :href="editSecurity()">Security</Tab>
            <Tab :href="editAppearance()">Appearance</Tab>
            <Tab v-if="isOwner" :href="editOrganization()">Organization</Tab>
        </Tabs>

        <section class="max-w-xl space-y-12 py-8">
            <h1 class="sr-only">Profile settings</h1>

            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name and email address"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >Save</Button
                        >
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </section>
    </div>
</template>
