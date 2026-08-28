<DropMenu>
  <template #trigger>
    <Button variant="secondary">Options</Button>
  </template>

<DropMenuItem href="/settings">Settings</DropMenuItem>
<DropMenuItem href="/profile">Profile</DropMenuItem>
<Separator class="my-1" />
<DropMenuItem href="/logout" method="post" as="button">Log out</DropMenuItem>
</DropMenu>
