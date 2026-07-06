<script setup lang="ts">
import { clientsIndex } from '@/actions/ClientController';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Clients', href: clientsIndex() },
            { title: 'Create' },
        ],
    },
});
</script>