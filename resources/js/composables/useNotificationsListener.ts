import { usePage } from '@inertiajs/vue3';
import { useEchoNotification } from '@laravel/echo-vue';
import { toast } from 'vue-sonner';
import { useNotifications } from '@/composables/useNotifications';
import type { DocumentsUploadBatchProcessedData } from '@/types/notification';

export function useNotificationsListener(): void {
    const page = usePage();
    const { receiveNotification } = useNotifications();
    const userId = page.props.auth.user?.id;

    if (!userId) {
        return;
    }

    useEchoNotification<DocumentsUploadBatchProcessedData>(
        `App.Models.User.${userId}`,
        (notification) => {
            const { id, type, ...data } = notification;

            receiveNotification({ id, type, data });
            toast.success(data.summary);
        },
    );
}
