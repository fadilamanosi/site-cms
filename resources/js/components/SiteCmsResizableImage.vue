<script setup lang="ts">
import { NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3';
import { computed, ref } from 'vue';
import { mediaUi } from '../mediaUi';

const props = defineProps(nodeViewProps);
const resizing = ref(false);
const altDraft = ref('');
const editingAlt = ref(false);

const align = computed(() => (props.node.attrs.align as string) || 'center');
const widthStyle = computed(() => {
    const width = props.node.attrs.width as string | null;
    if (!width) {
        return { maxWidth: '100%' };
    }

    return { width, maxWidth: '100%' };
});

function containerWidth(img: HTMLElement): number {
    const container = img.closest('.ProseMirror') as HTMLElement | null;

    return Math.max(1, (container?.clientWidth ?? img.parentElement?.clientWidth ?? 1) - 8);
}

function toPercent(px: number, max: number): string {
    const pct = Math.round((px / max) * 100);

    return `${Math.min(100, Math.max(10, pct))}%`;
}

function startResize(event: PointerEvent): void {
    event.preventDefault();
    event.stopPropagation();
    const img = (event.currentTarget as HTMLElement).closest('.scms-rte-img')?.querySelector('img');
    if (!img) {
        return;
    }
    const startX = event.clientX;
    const startWidth = img.getBoundingClientRect().width;
    const max = containerWidth(img);
    resizing.value = true;

    const onMove = (move: PointerEvent): void => {
        const delta = move.clientX - startX;
        const next = Math.min(max, Math.max(max * 0.1, Math.round(startWidth + delta)));
        // Live preview in px while dragging; commit as % on release.
        props.updateAttributes({ width: `${next}px` });
    };
    const onUp = (): void => {
        const current = img.getBoundingClientRect().width;
        props.updateAttributes({ width: toPercent(current, max) });
        resizing.value = false;
        window.removeEventListener('pointermove', onMove);
        window.removeEventListener('pointerup', onUp);
    };
    window.addEventListener('pointermove', onMove);
    window.addEventListener('pointerup', onUp);
}

function setAlign(next: 'left' | 'center' | 'right'): void {
    props.updateAttributes({ align: next });
}

function setWidth(next: string | null): void {
    props.updateAttributes({ width: next });
}

function startAlt(): void {
    altDraft.value = typeof props.node.attrs.alt === 'string' ? props.node.attrs.alt : '';
    editingAlt.value = true;
}

function saveAlt(): void {
    props.updateAttributes({ alt: altDraft.value.trim() });
    editingAlt.value = false;
}

function replaceImage(): void {
    mediaUi.show(props.node.attrs.src ?? '', (url, name) => {
        if (!url) {
            return;
        }
        const alt = name ? name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ') : (props.node.attrs.alt ?? '');
        props.updateAttributes({ src: url, alt });
    }, 'image');
}
</script>

<template>
    <NodeViewWrapper
        class="scms-rte-img"
        :class="[`is-${align}`, { 'is-selected': selected, 'is-resizing': resizing }]"
        data-drag-handle
    >
        <div class="scms-rte-img-frame" :style="widthStyle">
            <img :src="node.attrs.src" :alt="node.attrs.alt || ''" draggable="false" />
            <button
                v-if="selected"
                class="scms-rte-img-handle"
                type="button"
                aria-label="Resize image"
                @pointerdown="startResize"
            />
        </div>
        <div v-if="selected" class="scms-rte-img-tools" contenteditable="false">
            <button class="scms-btn" type="button" title="Align left" @click="setAlign('left')">Left</button>
            <button class="scms-btn" type="button" title="Align center" @click="setAlign('center')">Center</button>
            <button class="scms-btn" type="button" title="Align right" @click="setAlign('right')">Right</button>
            <button class="scms-btn" type="button" title="25% width" @click="setWidth('25%')">25%</button>
            <button class="scms-btn" type="button" title="50% width" @click="setWidth('50%')">50%</button>
            <button class="scms-btn" type="button" title="75% width" @click="setWidth('75%')">75%</button>
            <button class="scms-btn" type="button" title="Full width" @click="setWidth('100%')">100%</button>
            <button class="scms-btn" type="button" title="Reset size" @click="setWidth(null)">Auto</button>
            <button class="scms-btn" type="button" title="Edit alt text" @click="startAlt">Alt</button>
            <button class="scms-btn" type="button" title="Replace image" @click="replaceImage">Replace</button>
        </div>
        <div v-if="selected && editingAlt" class="scms-rte-img-alt" contenteditable="false">
            <input
                v-model="altDraft"
                type="text"
                placeholder="Describe this image"
                @keydown.enter.prevent="saveAlt"
                @keydown.escape.prevent="editingAlt = false"
            />
            <button class="scms-btn scms-btn-primary" type="button" @click="saveAlt">Save alt</button>
            <button class="scms-btn" type="button" @click="editingAlt = false">Cancel</button>
        </div>
    </NodeViewWrapper>
</template>
