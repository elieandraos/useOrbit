export interface NoteResource {
    id: number;
    body: string;
    pinned: boolean;
    created_by_name: string | null;
    created_at: string;
    can_update: boolean;
    can_delete: boolean;
}

export interface NoteConfig {
    max_length: number;
}
