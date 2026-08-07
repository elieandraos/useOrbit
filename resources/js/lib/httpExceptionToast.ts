import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';

export function initializeHttpExceptionToast(): void {
    router.on('httpException', (event) => {
        const response = (event as CustomEvent).detail?.response;

        if (response?.status !== 403) {
            return;
        }

        event.preventDefault();

        toast.error("You don't have permission to perform this action.");
    });
}
