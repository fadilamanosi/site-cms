import { reactive } from 'vue';
import type { ActiveField } from './types';

export const editorUi = reactive({
    active: null as ActiveField | null,
    rightOpen: true,
    setActive(next: ActiveField | null) {
        this.active = next;
        if (next) {
            this.rightOpen = true;
        }
    },
});
