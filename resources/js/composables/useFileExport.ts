import type { Ref } from 'vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';

export type UseFileExportMessages = {
    success?: string;
    error?: string;
};

export type UseFileExportReturn = {
    isExporting: Ref<boolean>;
    exportFile: (
        url: string,
        filename: string,
        messages?: UseFileExportMessages,
    ) => Promise<void>;
};

export function useFileExport(): UseFileExportReturn {
    const isExporting = ref(false);

    async function exportFile(
        url: string,
        filename: string,
        {
            success = 'Export downloaded.',
            error = 'Failed to export. Please try again.',
        }: UseFileExportMessages = {},
    ): Promise<void> {
        isExporting.value = true;

        try {
            const response = await fetch(url);

            if (!response.ok) {
                toast.error(error);

                return;
            }

            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = objectUrl;
            link.download = filename;
            link.click();
            URL.revokeObjectURL(objectUrl);

            toast.success(success);
        } catch {
            toast.error(error);
        } finally {
            isExporting.value = false;
        }
    }

    return { isExporting, exportFile };
}