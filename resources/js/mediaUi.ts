import { reactive } from 'vue';

export type MediaPick = (url: string, name?: string) => void;
export type MediaAccept = 'image' | 'file';

export const mediaUi = reactive({
    open: false,
    current: '',
    accept: 'file' as MediaAccept,
    pick: null as MediaPick | null,
    show(current = '', pick: MediaPick | null = null, accept: MediaAccept = 'file') {
        this.current = current;
        this.pick = pick;
        this.accept = accept;
        this.open = true;
    },
    close() {
        this.open = false;
        this.pick = null;
        this.accept = 'file';
    },
});
