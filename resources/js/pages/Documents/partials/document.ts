export interface DocumentResource {
    id: number;
    original_filename: string;
    mime_type: string;
    size_in_bytes: number;
    status: 'pending' | 'completed' | 'failed';
    uploaded_by_name: string | null;
    download_url: string | null;
    created_at: string;
}