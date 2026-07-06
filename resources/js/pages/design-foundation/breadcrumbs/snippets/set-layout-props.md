<script setup lang="ts">
import { setLayoutProps } from '@inertiajs/vue3';
import { clientsIndex, clientsShow } from '@/actions/ClientController';

const props = defineProps<{ client: Client }>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Clients', href: clientsIndex() },
        { title: 'Client', href: clientsShow({ client: props.client.slug }) },
        { title: 'Edit' },
    ],
});
</script>