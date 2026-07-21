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

export type DocumentsUploadBatchProcessedData = {
    total: number;
    completed: number;
    failed: number;
    documents: { id: number; status: 'completed' | 'failed' }[];
    client: { slug: string; name: string };
    summary: string;
};
