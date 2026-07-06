export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
    links: PaginationLink[];
}

export interface Paginated<T> {
    data: T[];
    meta: PaginationMeta;
}
