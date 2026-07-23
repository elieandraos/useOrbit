export type NotificationItem = {
    id: string;
    type: string;
    data: Record<string, unknown>;
    read_at: string | null;
    created_at: string;
};

export type Notifications = {
    unreadCount: number;
};

export type RecentNotificationsResponse = {
    data: NotificationItem[];
    next_cursor: string | null;
};

export type NotificationActor = {
    id: number;
    name: string;
};

export type NotificationSubject = {
    kind: string;
    slug: string;
    name: string;
};

export type NotificationEnvelope<TMeta> = {
    action: string;
    actor: NotificationActor | null;
    subject: NotificationSubject;
    meta: TMeta;
    summary: string;
};

export type DocumentsUploadMeta = {
    total: number;
    completed: number;
    failed: number;
    documents: { id: number; status: 'completed' | 'failed' }[];
};

export type DocumentsUploadBatchProcessedData =
    NotificationEnvelope<DocumentsUploadMeta>;
