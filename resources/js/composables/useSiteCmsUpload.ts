import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useSiteCms } from './useSiteCms';

export function useSiteCmsUpload() {
    const { siteCms } = useSiteCms();
    const uploading = ref(false);
    const error = ref('');
    const csrf = computed(() => {
        const token = usePage().props.csrf_token;
        return typeof token === 'string' ? token : '';
    });

    async function upload(file: File): Promise<string | null> {
        error.value = '';
        const maxBytes = siteCms.value.upload.maxKilobytes * 1024;
        if (file.size > maxBytes) {
            error.value = `File is larger than ${siteCms.value.upload.maxKilobytes} KB.`;
            return null;
        }

        uploading.value = true;
        const body = new FormData();
        body.append('file', file);
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? csrf.value;

        try {
            const response = await fetch(siteCms.value.routes.resources, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                },
                credentials: 'same-origin',
                body,
            });
            const json = (await response.json()) as {
                data?: { url?: string };
                message?: string;
                errors?: { file?: string[] };
            };
            if (!response.ok || !json.data?.url) {
                error.value = json.errors?.file?.[0] ?? json.message ?? 'Upload failed.';
                return null;
            }

            return json.data.url;
        } catch {
            error.value = 'Upload failed.';
            return null;
        } finally {
            uploading.value = false;
        }
    }

    return { upload, uploading, error, accept: computed(() => siteCms.value.upload.accept) };
}
