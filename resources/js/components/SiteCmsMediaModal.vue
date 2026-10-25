<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { mediaUi } from '../mediaUi';
import { fileNameFromHref, isRemoteHref } from '../fieldValue';
import { useSiteCmsMedia, type SiteCmsResource } from '../composables/useSiteCmsMedia';

const tab = ref<'library' | 'upload' | 'url'>('library');
const selected = ref('');
const remote = ref('');
const drop = ref(false);
const { items, meta, loading, uploading, error, accept, refresh, addFile, remove } = useSiteCmsMedia();

watch(
    () => mediaUi.open,
    (open) => {
        if (!open) {
            return;
        }
        selected.value = mediaUi.current;
        remote.value = isRemoteHref(mediaUi.current) ? mediaUi.current : '';
        tab.value = 'library';
        void refresh(1);
    },
);

function useUrl(url: string, name?: string): void {
    if (!url) {
        return;
    }
    mediaUi.pick?.(url, name);
    mediaUi.close();
}

function confirm(): void {
    if (tab.value === 'url') {
        useUrl(remote.value.trim());
        return;
    }
    const item = items.value.find((entry) => entry.url === selected.value);
    useUrl(selected.value, item?.name);
}

async function onFiles(files: FileList | File[]): Promise<void> {
    const file = files[0];
    if (!file) {
        return;
    }
    const url = await addFile(file);
    if (url) {
        selected.value = url;
        tab.value = 'library';
    }
}

function isImage(item: SiteCmsResource): boolean {
    return (item.mime_type ?? '').startsWith('image/');
}

function fileExt(name: string): string {
    const base = fileNameFromHref(name);
    const ext = base.split('.').pop();

    return ext && ext !== base ? ext : 'file';
}

function onDrop(event: DragEvent): void {
    drop.value = false;
    const files = event.dataTransfer?.files;
    if (files?.length) {
        void onFiles(files);
    }
}

function onKey(event: KeyboardEvent): void {
    if (event.key === 'Escape' && mediaUi.open) {
        mediaUi.close();
    }
}

onMounted(() => {
    document.addEventListener('keydown', onKey);
});
onUnmounted(() => {
    document.removeEventListener('keydown', onKey);
});
</script>

<template>
    <Teleport to="body">
        <Transition name="scms-pop">
            <div v-if="mediaUi.open" class="scms-media" role="dialog" aria-label="Media library" @click.self="mediaUi.close()">
                <div class="scms-media-card">
                    <header class="scms-media-h">
                        <span>Media</span>
                        <button class="scms-btn" type="button" aria-label="Close media" @click="mediaUi.close()">×</button>
                    </header>
                    <div class="scms-media-tabs">
                        <button type="button" :class="{ active: tab === 'library' }" @click="tab = 'library'">Library</button>
                        <button type="button" :class="{ active: tab === 'upload' }" @click="tab = 'upload'">Upload</button>
                        <button type="button" :class="{ active: tab === 'url' }" @click="tab = 'url'">From URL</button>
                    </div>
                    <template v-if="tab === 'library'">
                        <div class="scms-media-body">
                            <p v-if="error && items.length === 0" class="scms-error">{{ error }}</p>
                            <p v-else-if="loading" class="scms-muted">Loading…</p>
                            <p v-else-if="items.length === 0" class="scms-muted">{{ mediaUi.accept === 'image' ? 'No images yet. Upload one or paste a URL.' : 'No files yet. Upload one or paste a URL.' }}</p>
                            <div v-else class="scms-media-grid">
                                <button
                                    v-for="item in items"
                                    :key="item.id"
                                    type="button"
                                    class="scms-media-item"
                                    :class="{ active: selected === item.url }"
                                    :title="item.name"
                                    @click="selected = item.url"
                                    @dblclick="useUrl(item.url, item.name)"
                                >
                                    <img v-if="isImage(item)" :src="item.url" :alt="item.name" />
                                    <span v-else class="scms-media-file">{{ fileExt(item.name) }}</span>
                                    <span>{{ item.name }}</span>
                                </button>
                            </div>
                        </div>
                        <nav class="scms-pager" aria-label="Media pages">
                            <button
                                class="scms-btn"
                                type="button"
                                :disabled="meta.current_page <= 1"
                                @click="refresh(meta.current_page - 1)"
                            >
                                Prev
                            </button>
                            <span>Page {{ meta.current_page }} of {{ meta.last_page }} · {{ meta.total }} {{ meta.total === 1 ? 'file' : 'files' }}</span>
                            <button
                                class="scms-btn"
                                type="button"
                                :disabled="meta.current_page >= meta.last_page"
                                @click="refresh(meta.current_page + 1)"
                            >
                                Next
                            </button>
                        </nav>
                    </template>
                    <label
                        v-else-if="tab === 'upload'"
                        class="scms-media-drop"
                        :class="{ 'scms-media-drop--on': drop }"
                        @dragover.prevent="drop = true"
                        @dragleave="drop = false"
                        @drop.prevent="onDrop"
                    >
                        <span>{{ uploading ? 'Uploading…' : 'Drop a file or click to upload' }}</span>
                        <input type="file" :accept="accept" :disabled="uploading" @change="onFiles(($event.target as HTMLInputElement).files ?? [])" />
                        <span v-if="error" class="scms-error">{{ error }}</span>
                    </label>
                    <label v-else class="scms-field scms-media-url">
                        <span>{{ mediaUi.accept === 'image' ? 'Image URL' : 'File URL' }}</span>
                        <input
                            v-model="remote"
                            type="text"
                            inputmode="url"
                            :placeholder="mediaUi.accept === 'image' ? 'https://… or /path/to/image' : '/cv/file.pdf or https://…'"
                            autocomplete="off"
                        />
                    </label>
                    <footer class="scms-media-f">
                        <button
                            v-if="tab === 'library' && selected && items.some((item) => item.url === selected)"
                            class="scms-btn"
                            type="button"
                            @click="remove(items.find((item) => item.url === selected)?.id as number)"
                        >
                            Delete
                        </button>
                        <span class="scms-media-spacer" />
                        <button class="scms-btn" type="button" @click="mediaUi.close()">Cancel</button>
                        <button
                            v-if="mediaUi.pick"
                            class="scms-btn scms-btn-primary"
                            type="button"
                            :disabled="tab === 'url' ? !remote.trim() : !selected"
                            @click="confirm"
                        >
                            Use
                        </button>
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
