<DropMenu align="start">
  <template #trigger>
    <Button variant="secondary">
      <template #leading><Tag class="size-4" /></template>
      Add tag
    </Button>
  </template>

  <div class="flex items-center gap-1.5 rounded-md border border-border px-2 py-1.5 mx-1 mb-1">
    <Search class="size-3.5 text-tertiary shrink-0" />
    <input
      type="text"
      placeholder="Find or create…"
      class="flex-1 bg-transparent text-sm outline-none placeholder:text-tertiary"
      @click.stop
    />
  </div>

<DropMenuItem>Marketing</DropMenuItem>
<DropMenuItem>Renewal</DropMenuItem>
<DropMenuItem>VIP</DropMenuItem>
</DropMenu>
