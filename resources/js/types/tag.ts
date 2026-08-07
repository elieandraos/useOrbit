export interface TagResource {
    id: number;
    name: string;
    usage_count?: number;
    can_update: boolean;
    can_delete: boolean;
}
