<script setup lang="ts">
import { NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3';
import { computed, ref } from 'vue';
import { mediaUi } from '../mediaUi';

const props = defineProps(nodeViewProps);
const resizing = ref(false);

const align = computed(() => (props.node.attrs.align as string) || 'center');
const widthStyle = computed(() => {
    const width = props.node.attrs.width as string | null;
    return width ? { width } : undefined;
});

function startResize(event: PointerEvent): void {
    event.preventDefault();
    event.stopPropagation();
    const img = (event.currentTarget as HTMLElement).closest('.scms-rte-img')?.querySelector('img');
    if (!img) {
        return;
    }
    const startX = event.clientX;
    const startWidth = img.getBoundingClientRect().width;
    const container = img.closest('.ProseMirror') as HTMLElement | null;
    const max = container ? container.clientWidth - 8 : 1200;
    resizing.value = true;

    const onMove = (move: PointerEvent): void => {
        const delta = move.clientX - startX;
        const next = Math.min(max, Math.max(96, Math.round(startWidth + delta)));
        props.updateAttributes({ width: `${next}px` });
    };
    const onUp = (): void => {
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

function editAlt(): void {
    const current = typeof props.node.attrs.alt === 'string' ? props.node.attrs.alt : '';
    const next = window.prompt('Image alt text', current);
    if (next === null) {
        return;
    }
    props.updateAttributes({ alt: next });
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
            <button class="scms-btn" type="button" title="Edit alt text" @click="editAlt">Alt</button>
            <button class="scms-btn" type="button" title="Replace image" @click="replaceImage">Replace</button>
        </div>
    </NodeViewWrapper>
</template>
