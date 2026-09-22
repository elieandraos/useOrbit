import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Building2, Home, Shield, UserCog, Users } from '@lucide/vue';
import type { FunctionalComponent } from 'vue';
import { dashboard } from '@/routes';
import { index as agentsIndex } from '@/routes/agents';
import { index as carriersIndex } from '@/routes/carriers';
import { index as clientsIndex } from '@/routes/clients';
import { index as policiesIndex } from '@/routes/policies';

export type NavItem = {
    label: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: FunctionalComponent;
};

export const navItems: NavItem[] = [
    { label: 'Dashboard', href: dashboard(), icon: Home },
    { label: 'Clients', href: clientsIndex(), icon: Users },
    { label: 'Carriers', href: carriersIndex(), icon: Building2 },
    { label: 'Agents', href: agentsIndex(), icon: UserCog },
    { label: 'Policies', href: policiesIndex(), icon: Shield },
];
