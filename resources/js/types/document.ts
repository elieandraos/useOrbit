export interface DocumentResource {
    id: number;
    original_filename: string;
    mime_type: string;
    size_in_bytes: number;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    uploaded_by_name: string | null;
    download_url: string | null;
    error_message?: string;
    created_at: string;
    can_delete: boolean;
}

export interface DocumentUploadConfig {
    max_size_bytes: number;
    allowed_extensions: string[];
    max_files_per_batch: number;
}

export interface UploadRowItem {
    kind: 'upload';
    id: string;
    name: string;
    progress: number;
    status: 'uploading' | 'error';
    errorMessage?: string;
}

export interface DocumentRowItem extends DocumentResource {
    kind: 'document';
}

export type DocumentListItem = UploadRowItem | DocumentRowItem;
