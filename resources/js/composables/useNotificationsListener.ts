import { usePage } from '@inertiajs/vue3';
import { useEchoNotification } from '@laravel/echo-vue';
import { toast } from 'vue-sonner';
import { useNotifications } from '@/composables/useNotifications';
import { DOCUMENTS_UPLOADED } from '@/lib/notificationTypes';
import type {
    DocumentsUploadMeta,
    NotificationEnvelope,
} from '@/types/notification';

export function useNotificationsListener(): void {
    const page = usePage();
    const { receiveNotification } = useNotifications();
    const userId = page.props.auth.user?.id;

    if (!userId) {
        return;
    }

    useEchoNotification<NotificationEnvelope<unknown>>(
        `App.Models.User.${userId}`,
        (notification) => {
            const { id, type, ...data } = notification;

            receiveNotification({ id, type, data });

            if (data.action !== DOCUMENTS_UPLOADED) {
                return;
            }

            const { failed, completed } = data.meta as DocumentsUploadMeta;

            if (failed > 0 && completed === 0) {
                toast.error(data.summary);
            } else if (failed > 0) {
                toast.warning(data.summary);
            } else {
                toast.success(data.summary);
            }
        },
    );
}
