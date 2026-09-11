# Inertia Forms

## Prefer the `<Form>` component

For Inertia v3 form pages in this stack, prefer the `<Form>` component over `useForm()` when the form can be represented by named inputs and the Wayfinder route helper can provide the action and method.

```vue
<Form v-bind="routeHelper.form()" v-slot="{ errors, processing }">
    <!-- named inputs -->
</Form>
```

Bind native form controls with `name="..."` so the `<Form>` component serializes the submitted fields. Keep using `useForm()` when `form.transform()` or another genuinely client-side transformation is required and cannot reasonably be handled by the backend.

## Make custom controls serializable

A custom component that is driven only by `v-model` and does not render a native named control should expose an optional `name` prop and render a hidden input when `name` is provided. The hidden input mirrors the component's current value so it participates in `<Form>` serialization.

```vue
<script setup lang="ts">
const props = defineProps<{
    modelValue: string;
    name?: string;
}>();
</script>

<template>
    <!-- visible control omitted -->
    <input v-if="props.name" type="hidden" :name="props.name" :value="props.modelValue" />
</template>
```

For components with internal derived values, keep the existing reactive state and derive the hidden value from that state rather than maintaining a second independent form value.

For invisible defaults that need no custom control, use a normal hidden input in the page itself.

Native controls and components that already forward `$attrs` to their native input/select do not need a custom serialization layer.
