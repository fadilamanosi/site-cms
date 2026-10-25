import { computed, toValue, type MaybeRefOrGetter } from 'vue';
import { editorUi } from '../editorUi';
import { unwrapEntry } from '../fieldValue';
import type { SiteCmsKind } from '../types';
import { useSiteCms } from './useSiteCms';

export function useSiteField(
    fieldKey: MaybeRefOrGetter<string | undefined>,
    kind: SiteCmsKind,
    fallback: MaybeRefOrGetter<unknown>,
) {
    const { siteCms } = useSiteCms();

    const resolved = computed(() => {
        const key = toValue(fieldKey);
        if (!key) {
            return toValue(fallback);
        }
        const entry = editorUi.live[key] ?? siteCms.value.values[key];
        if (!entry) {
            return toValue(fallback);
        }
        const raw = unwrapEntry(entry);
        if (raw === undefined || raw === null || raw === '') {
            return toValue(fallback);
        }
        if (entry.kind !== kind && kind !== 'link' && kind !== 'button') {
            return raw;
        }

        return raw;
    });

    const editable = computed(() => Boolean(toValue(fieldKey)) && siteCms.value.enabled);

    return { resolved, editable, siteCms };
}
