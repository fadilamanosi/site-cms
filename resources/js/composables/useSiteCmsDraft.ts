import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { storedValue } from '../fieldValue';
import type { SiteCmsKind, SiteCmsPageProps } from '../types';
import { useSiteCms } from './useSiteCms';

export function useSiteCmsDraft() {
    const { siteCms } = useSiteCms();
    const page = usePage<SiteCmsPageProps>();
    const saving = ref(false);
    const error = ref('');

    async function save(pageKey: string, fieldKey: string, kind: SiteCmsKind, value: unknown): Promise<boolean> {
        error.value = '';
        saving.value = true;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        try {
            const response = await fetch(siteCms.value.routes.draft, {
                method: 'PUT',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(token ? { 'X-CSRF-TOKEN': token } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    page_key: pageKey,
                    fields: [{ field_key: fieldKey, kind, value }],
                }),
            });
            const json = (await response.json()) as { ok?: boolean; draft?: { dirty: boolean; count: number } };
            if (!response.ok) {
                error.value = 'Could not save.';
                return false;
            }
            if (page.props.siteCms) {
                page.props.siteCms.values = {
                    ...page.props.siteCms.values,
                    [fieldKey]: { kind, value: storedValue(kind, value) },
                };
                if (json.draft) {
                    page.props.siteCms.draft = json.draft;
                } else {
                    page.props.siteCms.draft = { dirty: true, count: Math.max(1, page.props.siteCms.draft?.count ?? 0) };
                }
            }

            return true;
        } catch {
            error.value = 'Could not save.';
            return false;
        } finally {
            saving.value = false;
        }
    }

    return { save, saving, error };
}
