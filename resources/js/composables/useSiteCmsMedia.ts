import { computed, ref } from 'vue';
import { mediaUi } from '../mediaUi';
import { useSiteCms } from './useSiteCms';
import { useSiteCmsUpload } from './useSiteCmsUpload';

export type SiteCmsResource = {
    id: number;
    name: string;
    url: string;
    mime_type: string | null;
};

export type SiteCmsResourceMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    from: number;
    to: number;
    total: number;
};

const emptyMeta: SiteCmsResourceMeta = {
    current_page: 1,
    last_page: 1,
    per_page: 24,
    from: 0,
    to: 0,
    total: 0,
};

function csrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

export function useSiteCmsMedia() {
    const { siteCms } = useSiteCms();
    const { upload, uploading, error } = useSiteCmsUpload();
    const items = ref<SiteCmsResource[]>([]);
    const meta = ref<SiteCmsResourceMeta>({ ...emptyMeta });
    const loading = ref(false);
    const accept = computed(() =>
        mediaUi.accept === 'image'
            ? (siteCms.value.upload.imageAccept || 'image/*')
            : siteCms.value.upload.accept,
    );

    async function refresh(nextPage = 1): Promise<void> {
        loading.value = true;
        try {
            const type = mediaUi.accept === 'image' ? '&type=image' : '';
            const response = await fetch(`${siteCms.value.routes.resources}?page=${Math.max(1, nextPage)}${type}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            const json = (await response.json()) as {
                data?: SiteCmsResource[];
                meta?: Partial<SiteCmsResourceMeta>;
                message?: string;
            };
            if (!response.ok) {
                error.value = json.message ?? 'Could not load media.';
                items.value = [];
                meta.value = { ...emptyMeta };
                return;
            }
            items.value = Array.isArray(json.data) ? json.data : [];
            meta.value = {
                current_page: json.meta?.current_page ?? 1,
                last_page: Math.max(1, json.meta?.last_page ?? 1),
                per_page: json.meta?.per_page ?? 24,
                from: json.meta?.from ?? 0,
                to: json.meta?.to ?? 0,
                total: json.meta?.total ?? items.value.length,
            };
        } catch {
            error.value = 'Could not load media.';
            items.value = [];
            meta.value = { ...emptyMeta };
        } finally {
            loading.value = false;
        }
    }

    async function addFile(file: File): Promise<string | null> {
        const url = await upload(file);
        if (url) {
            await refresh(1);
        }

        return url;
    }

    async function remove(id: number): Promise<void> {
        const token = csrfToken();
        await fetch(`${siteCms.value.routes.resources}/${id}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(token ? { 'X-CSRF-TOKEN': token } : {}),
            },
            credentials: 'same-origin',
        });
        const next = items.value.length <= 1 && meta.value.current_page > 1
            ? meta.value.current_page - 1
            : meta.value.current_page;
        await refresh(next);
    }

    return { items, meta, loading, uploading, error, accept, refresh, addFile, remove };
}
