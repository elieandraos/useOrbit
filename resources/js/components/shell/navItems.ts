import type { InertiaLinkProps } from '@inertiajs/vue3';
import { Building2, Home, Users } from '@lucide/vue';
import type { FunctionalComponent } from 'vue';
import { dashboard } from '@/routes';
import { index as carriersIndex } from '@/routes/carriers';
import { index as clientsIndex } from '@/routes/clients';

export type NavItem = {
    label: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: FunctionalComponent;
};

export const navItems: NavItem[] = [
    { label: 'Dashboard', href: dashboard(), icon: Home },
    { label: 'Clients', href: clientsIndex(), icon: Users },
    { label: 'Carriers', href: carriersIndex(), icon: Building2 },
];
