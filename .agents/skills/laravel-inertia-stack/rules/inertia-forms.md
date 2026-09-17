# Inertia Forms

Build a form with the `<Form>` component and named inputs, letting a Wayfinder route helper supply
the action and method:

```vue
<Form v-bind="routeHelper.form()" v-slot="{ errors, processing }">
    <!-- named inputs -->
</Form>
```

`inertia-vue-development` and `wayfinder-development` already document `<Form>`'s own mechanics and
the Wayfinder integration; this stack's own fallback rule is narrower: reach for `useForm()` only when
a transformation must run client-side and genuinely cannot be moved to the backend — the same bias
toward backend-owned coercion that `request-normalization.md` states for request input generally, not
a separate judgment call for forms. This file also adds the one delta Boost doesn't cover: making a
custom Vue control participate in `<Form>` serialization.

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
