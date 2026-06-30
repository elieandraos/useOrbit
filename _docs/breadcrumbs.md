# Breadcrumbs

## Data shape

```ts
type BreadcrumbItem = {
    title: string;
    href: string; // omit on the last item — it renders as the current page, not a link
};
```

## Declaring breadcrumbs on a page

Pages never import `AppLayout` directly. Inertia v3 resolves it automatically via `app.ts`. Whatever you pass in `defineOptions({ layout: { ... } })` or `setLayoutProps({ ... })` is forwarded to `AppLayout` as props.

### Static — when crumbs don't depend on runtime props

```js
defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Clients', href: clientsIndex() },
            { title: 'Create' }, // last item has no href
        ],
    },
})
```

### Dynamic — when crumbs need runtime props (e.g. a slug from `defineProps`)

```js
const props = defineProps<{ client: Client }>()

setLayoutProps({
    breadcrumbs: [
        { title: 'Clients', href: clientsIndex() },
        { title: 'Client', href: clientsShow({ client: props.client.slug }) },
        { title: 'Edit' },
    ],
})
```

`setLayoutProps` is the runtime equivalent of `defineOptions({ layout: { ... } })` — same result, evaluated after props resolve.

## How `AppLayout` receives them

`AppLayout.vue` declares the prop and Inertia passes it automatically:

```ts
defineProps<{ breadcrumbs?: BreadcrumbItem[] }>()
```

## What `Breadcrumbs.vue` renders

- Every item **except the last** → Inertia `<Link>` with a `<ChevronRight>` separator
- The **last item** → `<span aria-current="page" class="text-primary">` (no link, highlighted)

## Current state

`AppLayout` receives the `breadcrumbs` prop correctly but its template does not render `<Breadcrumbs>` yet — the data arrives but nothing displays it. To wire it up, pass `:breadcrumbs="breadcrumbs"` to `<AppTopNav>` or render `<Breadcrumbs>` directly in `AppLayout`'s template.