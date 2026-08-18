<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/two-factor/login';

defineOptions({
    layout: {
        title: 'Two-factor authentication',
        description:
            'Enter the authentication code from your authenticator app',
    },
});

const usingRecoveryCode = ref(false);

function toggleRecoveryCode(): void {
    usingRecoveryCode.value = !usingRecoveryCode.value;
}
</script>

<template>
    <Head title="Two-factor authentication" />

    <Form
        v-bind="store.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div v-if="!usingRecoveryCode" class="grid gap-2">
            <Label for="code">Authentication code</Label>
            <Input
                id="code"
                name="code"
                inputmode="numeric"
                autocomplete="one-time-code"
                autofocus
                required
                placeholder="123456"
            />
            <InputError :message="errors.code" />
        </div>

        <div v-else class="grid gap-2">
            <Label for="recovery_code">Recovery code</Label>
            <Input
                id="recovery_code"
                name="recovery_code"
                autocomplete="off"
                autofocus
                required
                placeholder="xxxxx-xxxxx"
            />
            <InputError :message="errors.recovery_code" />
        </div>

        <Button type="submit" class="w-full" :disabled="processing">
            <Spinner v-if="processing" />
            Continue
        </Button>

        <button
            type="button"
            class="text-center text-sm text-muted-foreground underline-offset-4 hover:underline"
            @click="toggleRecoveryCode"
        >
            {{
                usingRecoveryCode
                    ? 'Use an authentication code instead'
                    : 'Use a recovery code instead'
            }}
        </button>
    </Form>
</template>
