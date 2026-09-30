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

## Client-side constraints must not hide server-valid values

A reusable control's convenience defaults — a date picker's year range, a `min`/`max`, a trimmed option
list — must not make values the Form Request accepts impossible to enter, and a persisted value that is
still valid must remain representable when editing. When one feature's valid range differs from the
control's default, pass the bound from the page rather than changing the shared default:

```vue
<!-- this field accepts future dates; the page, not the shared control, says so -->
<DateInput name="starts_on" v-model="startsOn" :max-year="currentYear + 5" />
```

This is not a requirement to mirror server rules in the UI or to teach shared controls domain rules:
the Form Request stays authoritative, and a control only has to avoid excluding what it accepts. For a
closed choice list, see `enum-options.md`.
