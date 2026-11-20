<script setup lang="ts">
import { NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3';
import { computed, ref } from 'vue';
import type { GalleryImageItem, GalleryLayout } from '../extensions/imageGallery';
import { galleryLayoutClass } from '../extensions/imageGallery';
import { mediaUi } from '../mediaUi';

const props = defineProps(nodeViewProps);
const open = ref<number | null>(null);

const layout = computed(() => (props.node.attrs.layout as GalleryLayout) || 'grid');
const items = computed(() => (Array.isArray(props.node.attrs.items) ? props.node.attrs.items : []) as GalleryImageItem[]);
const blockClass = computed(() => galleryLayoutClass(layout.value));

function setLayout(next: GalleryLayout): void {
    props.updateAttributes({ layout: next });
}

function setItems(next: GalleryImageItem[]): void {
    props.updateAttributes({ items: next });
}

function patch(index: number, key: keyof GalleryImageItem, value: string): void {
    setItems(items.value.map((item, current) => (current === index ? { ...item, [key]: value } : item)));
}

function remove(index: number): void {
    setItems(items.value.filter((_, current) => current !== index));
    if (open.value === index) {
        open.value = null;
    } else if (open.value !== null && open.value > index) {
        open.value -= 1;
    }
}

function move(index: number, direction: number): void {
    const next = index + direction;
    if (next < 0 || next >= items.value.length) {
        return;
    }
    const copy = [...items.value];
    const current = copy[index];
    const other = copy[next];
    if (!current || !other) {
        return;
    }
    copy[index] = other;
    copy[next] = current;
    setItems(copy);
    open.value = next;
}

function addImage(): void {
    mediaUi.show(
        '',
        (url, name) => {
            const next = [
                ...items.value,
                {
                    src: url,
                    alt: name ? name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ') : '',
                    caption: '',
                },
            ];
            setItems(next);
            open.value = next.length - 1;
        },
        'image',
    );
}

function replaceImage(index: number): void {
    const current = items.value[index];
    mediaUi.show(
        current?.src || '',
        (url, name) => {
            patch(index, 'src', url);
            if (!current?.alt && name) {
                patch(index, 'alt', name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' '));
            }
        },
        'image',
    );
}

function itemLabel(item: GalleryImageItem, index: number): string {
    return item.caption || item.alt || `Image ${index + 1}`;
}
</script>

<template>
    <NodeViewWrapper class="scms-rte-gallery" data-drag-handle>
        <div class="scms-rte-gallery-toolbar" contenteditable="false">
            <label class="scms-field scms-rte-gallery-layout">
                <span>Layout</span>
                <select :value="layout" @change="setLayout(($event.target as HTMLSelectElement).value as GalleryLayout)">
                    <option value="grid">Grid · 3</option>
                    <option value="grid-two">Grid · 2</option>
                    <option value="grid-four">Grid · 4</option>
                    <option value="grid-six">Grid · 6</option>
                    <option value="portrait">Portrait</option>
                </select>
            </label>
            <button class="scms-btn scms-btn-primary" type="button" @click="addImage">Add image</button>
            <span class="scms-muted">{{ items.length }} {{ items.length === 1 ? 'photo' : 'photos' }}</span>
        </div>

        <div :class="blockClass" contenteditable="false">
            <figure
                v-for="(item, index) in items"
                :key="`${item.src}-${index}`"
                :class="{ 'perspective-post-portrait': layout === 'portrait', 'is-open': open === index }"
            >
                <button type="button" class="scms-rte-gallery-thumb" @click="open = open === index ? null : index">
                    <img v-if="item.src" :src="item.src" :alt="item.alt || ''" />
                    <span v-else class="scms-rte-gallery-empty">No image</span>
                </button>
                <figcaption>{{ itemLabel(item, index) }}</figcaption>
                <div v-if="open === index" class="scms-rte-gallery-edit">
                    <div class="scms-gallery-tools">
                        <button type="button" class="scms-btn" :disabled="index === 0" @click="move(index, -1)">Up</button>
                        <button type="button" class="scms-btn" :disabled="index === items.length - 1" @click="move(index, 1)">Down</button>
                        <button type="button" class="scms-btn" @click="replaceImage(index)">Replace</button>
                        <button type="button" class="scms-btn" @click="remove(index)">Remove</button>
                    </div>
                    <label class="scms-field">
                        <span>Alt text</span>
                        <input :value="item.alt" type="text" @input="patch(index, 'alt', ($event.target as HTMLInputElement).value)" />
                    </label>
                    <label class="scms-field">
                        <span>Caption</span>
                        <input :value="item.caption" type="text" @input="patch(index, 'caption', ($event.target as HTMLInputElement).value)" />
                    </label>
                </div>
            </figure>
            <button
                v-if="items.length === 0"
                type="button"
                class="scms-btn scms-rte-gallery-empty-add"
                @click="addImage"
            >
                Add photos to this gallery
            </button>
        </div>
    </NodeViewWrapper>
</template>
