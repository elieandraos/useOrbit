import { router } from '@inertiajs/vue3';

/**
 * End the policy Create flow: its pages' history is encrypted, so forgetting the key makes browser Back, Forward
 * or a bfcache restore re-fetch them fresh, without anything entered in them.
 */
export function endPolicyCreateFlow(): void {
    router.clearHistory();
}
