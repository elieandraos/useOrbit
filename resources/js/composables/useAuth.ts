import { usePage } from '@inertiajs/vue3';
import type { ComputedRef, DeepReadonly } from 'vue';
import { computed, readonly } from 'vue';

export type UseAuthReturn = {
    isPrivileged: DeepReadonly<ComputedRef<boolean>>;
};

const page = usePage();
const isPrivilegedReactive = computed(() => page.props.auth.user.is_privileged);

export function useAuth(): UseAuthReturn {
    return {
        isPrivileged: readonly(isPrivilegedReactive),
    };
}
