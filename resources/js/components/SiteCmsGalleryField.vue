<script setup lang="ts">
import { ref } from 'vue';
import { mediaUi } from '../mediaUi';
import type { GalleryItem } from '../types';
import SiteCmsImageField from './SiteCmsImageField.vue';

const items = defineModel<GalleryItem[]>({ default: () => [] });
const open = ref<number | null>(null);

function toggle(index: number): void {
    open.value = open.value === index ? null : index;
}

function add(): void {
    mediaUi.show(
        '',
        (url, name) => {
            items.value = [
                ...items.value,
                {
                    src: url,
                    alt: name ? name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ') : '',
                    caption: '',
                },
            ];
            open.value = items.value.length - 1;
        },
        'image',
    );
}

function remove(index: number): void {
    items.value = items.value.filter((_, current) => current !== index);
    if (open.value === null) {
        return;
    }
    if (open.value === index) {
        open.value = null;
    } else if (open.value > index) {
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
    items.value = copy;
    if (open.value === index) {
        open.value = next;
    } else if (open.value === next) {
        open.value = index;
    }
}

function patch(index: number, key: keyof GalleryItem, value: string): void {
    items.value = items.value.map((item, current) => (current === index ? { ...item, [key]: value } : item));
}

function itemLabel(item: GalleryItem, index: number): string {
    return item.caption || item.alt || `Image ${index + 1}`;
}
</script>

<template>
    <div class="scms-gallery">
        <div v-for="(item, index) in items" :key="index" class="scms-gallery-item" :class="{ 'is-open': open === index }">
            <button
                class="scms-gallery-h"
                type="button"
                :aria-expanded="open === index"
                @click="toggle(index)"
            >
                <img v-if="item.src" class="scms-gallery-thumb" :src="item.src" alt="" />
                <span v-else class="scms-gallery-thumb scms-gallery-thumb--empty" />
                <span>{{ index + 1 }}. {{ itemLabel(item, index) }}</span>
                <svg
                    class="scms-chevron"
                    :class="{ 'scms-chevron--up': open === index }"
                    viewBox="0 0 12 12"
                    aria-hidden="true"
                >
                    <path d="M2.5 4.25 L6 7.75 L9.5 4.25" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <div v-if="open === index" class="scms-gallery-body">
                <div class="scms-gallery-tools">
                    <button type="button" class="scms-btn" :disabled="index === 0" @click="move(index, -1)">Up</button>
                    <button type="button" class="scms-btn" :disabled="index === items.length - 1" @click="move(index, 1)">
                        Down
                    </button>
                    <button type="button" class="scms-btn" @click="remove(index)">Remove</button>
                </div>
                <SiteCmsImageField
                    :model-value="item.src"
                    :alt="item.alt"
                    with-alt
                    :label="`Image ${index + 1}`"
                    @update:model-value="patch(index, 'src', $event)"
                    @update:alt="patch(index, 'alt', $event)"
                />
                <label class="scms-field">
                    <span>Caption</span>
                    <input :value="item.caption" type="text" @input="patch(index, 'caption', ($event.target as HTMLInputElement).value)" />
                </label>
            </div>
        </div>
        <button class="scms-btn" type="button" @click="add">Add image</button>
    </div>
</template>
