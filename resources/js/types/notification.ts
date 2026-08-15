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

export type MemberJoinedMeta = {
    member: {
        id: number;
        name: string;
        email: string;
        role: string | null;
    };
};

export type MemberJoinedData = NotificationEnvelope<MemberJoinedMeta>;

export type ResourceEventMeta = Record<string, never>;

export type ResourceArchivedData = NotificationEnvelope<ResourceEventMeta>;

export type ResourceUnarchivedData = NotificationEnvelope<ResourceEventMeta>;

export type MemberRoleChangedMeta = {
    member: {
        id: number;
        name: string;
        email: string;
    };
    from_role: string;
    to_role: string;
};

export type MemberRoleChangedData = NotificationEnvelope<MemberRoleChangedMeta>;

export type YourRoleChangedMeta = {
    from_role: string;
    to_role: string;
};

export type YourRoleChangedData = NotificationEnvelope<YourRoleChangedMeta>;

export type MemberRemovedMeta = {
    member: {
        id: number;
        name: string;
        email: string;
        role: string;
    };
    successor: {
        id: number;
        name: string;
    };
};

export type MemberRemovedData = NotificationEnvelope<MemberRemovedMeta>;

export type ResourceMessageMeta = {
    reason: 'needs_review' | 'for_attention' | 'wants_input';
    parent: NotificationSubject | null;
};

export type ResourceMessageData = NotificationEnvelope<ResourceMessageMeta>;
