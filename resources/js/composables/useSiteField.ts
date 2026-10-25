import { computed, toValue, type MaybeRefOrGetter } from 'vue';
import { useSiteCms } from './useSiteCms';
import type { SiteCmsKind } from '../types';

function unwrap(entry: unknown): unknown {
    if (entry && typeof entry === 'object' && 'value' in entry) {
        const inner = (entry as { value: unknown }).value;
        if (inner && typeof inner === 'object' && 'value' in inner && !('label' in inner) && !('items' in inner)) {
            return (inner as { value: unknown }).value;
        }
        return inner;
    }
    return undefined;
}

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
        const entry = siteCms.value.values[key];
        if (!entry || entry.kind !== kind) {
            const raw = unwrap(entry);
            if (raw !== undefined && raw !== null && raw !== '') {
                return raw;
            }
            return toValue(fallback);
        }
        const raw = unwrap(entry);
        if (raw === undefined || raw === null || raw === '') {
            return toValue(fallback);
        }
        return raw;
    });

    const editable = computed(() => Boolean(toValue(fieldKey)) && siteCms.value.enabled);

    return { resolved, editable, siteCms };
}
