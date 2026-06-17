<DropMenu>
  <template #trigger>
    <button class="p-1 rounded hover:bg-sunken">
      <MoreHorizontal class="size-4 text-secondary" />
    </button>
  </template>

  <DropMenuItem href="/edit">
    <template #leading><Pencil class="size-4" /></template>
    Edit
  </DropMenuItem>
  <DropMenuItem href="/duplicate">
    <template #leading><Copy class="size-4" /></template>
    Duplicate
  </DropMenuItem>
  <Separator class="my-1" />
  <DropMenuItem danger>
    <template #leading><Trash2 class="size-4" /></template>
    Delete
  </DropMenuItem>
</DropMenu>
