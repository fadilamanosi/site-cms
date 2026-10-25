import { reactive } from 'vue';
import { storedValue } from './fieldValue';
import { isSiteCmsMessage } from './previewBridge';
import type { ActiveField, SiteCmsKind, SiteCmsValue } from './types';

export const editorUi = reactive({
    active: null as ActiveField | null,
    live: {} as Record<string, SiteCmsValue>,
    setActive(next: ActiveField | null) {
        this.active = next;
    },
    patch(fieldKey: string, kind: SiteCmsKind, value: unknown) {
        this.live[fieldKey] = { kind, value: storedValue(kind, value) };
    },
});

if (typeof window !== 'undefined') {
    window.addEventListener('message', (event: MessageEvent) => {
        if (event.origin !== window.location.origin || !isSiteCmsMessage(event.data)) {
            return;
        }
        if (event.data.type !== 'patch') {
            return;
        }
        editorUi.patch(event.data.fieldKey, event.data.kind, event.data.value);
    });
}
