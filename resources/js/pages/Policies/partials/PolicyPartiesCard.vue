<script setup lang="ts">
import { computed } from 'vue';
import { Avatar } from '@/components/ui/avatar';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { IdentityLink } from '@/components/ui/identity-link';
import { show as agentsShow } from '@/routes/agents';
import { show as carriersShow } from '@/routes/carriers';
import { show as clientsShow } from '@/routes/clients';
import type { PolicyResource } from '@/types/policy';

const props = defineProps<{
    policy: PolicyResource;
}>();

const parties = computed(() => [
    {
        role: 'Client',
        name: props.policy.client.full_name,
        href: clientsShow(props.policy.client.slug),
    },
    {
        role: 'Insurance company',
        name: props.policy.carrier.name,
        href: carriersShow(props.policy.carrier.slug),
    },
    ...(props.policy.agent
        ? [
              {
                  role: 'Agent',
                  name: props.policy.agent.full_name,
                  href: agentsShow(props.policy.agent.slug),
              },
          ]
        : []),
]);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Parties</CardTitle>
        </CardHeader>
        <CardContent>
            <ul class="flex flex-col gap-4">
                <li
                    v-for="party in parties"
                    :key="party.role"
                    class="flex min-w-0 items-center gap-3"
                >
                    <Avatar :name="party.name" size="md" />
                    <div class="flex min-w-0 flex-1 flex-col gap-1">
                        <IdentityLink
                            :href="party.href"
                            class="max-w-full self-start text-[13.5px] leading-none"
                        >
                            {{ party.name }}
                        </IdentityLink>
                        <p
                            class="font-mono text-[10.5px] tracking-[0.06em] text-tertiary uppercase"
                        >
                            {{ party.role }}
                        </p>
                    </div>
                </li>
            </ul>
        </CardContent>
    </Card>
</template>
