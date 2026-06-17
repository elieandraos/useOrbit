<DropMenu>
  <template #trigger>
    <Button variant="secondary">Filter</Button>
  </template>

  <div class="px-2 py-1.5 text-xs font-mono text-tertiary uppercase tracking-widest">
    Status
  </div>
  <label
    v-for="option in options"
    :key="option.value"
    class="flex items-center gap-2 rounded-[6px] px-2 py-1.5 text-sm text-primary hover:bg-sunken cursor-pointer"
    @click.stop
  >
    <Checkbox :model-value="selected.includes(option.value)" @update:model-value="toggle(option.value)" />
    {{ option.label }}
  </label>
</DropMenu>
