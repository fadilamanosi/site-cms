<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { editorUi } from '../editorUi';
import { asButton, prettyLabel } from '../fieldValue';
import { useSiteCms } from '../composables/useSiteCms';
import { useSiteField } from '../composables/useSiteField';
import { isSiteCmsMessage, postToParent, SITE_CMS_SOURCE } from '../previewBridge';
import type { ActiveField, SiteCmsKind } from '../types';
import '../../css/site-cms-preview.css';

const props = withDefaults(
    defineProps<{
        fieldKey: string;
        kind: SiteCmsKind;
        label?: string;
        defaultValue?: unknown;
    }>(),
    { label: '', defaultValue: '' },
);

const { siteCms } = useSiteCms();
const { resolved } = useSiteField(() => props.fieldKey, props.kind, () => props.defaultValue);
const shell = ref<HTMLElement | null>(null);

const active = computed(() => siteCms.value.enabled && editorUi.active?.fieldKey === props.fieldKey);
const block = computed(() => props.kind === 'image' || props.kind === 'gallery');

function fieldFrom(target: HTMLElement | null): ActiveField {
    const img = target?.querySelector('img');
    const link = target?.querySelector('a');
    const fromDom =
        props.kind === 'text'
            ? target?.innerText?.trim()
            : props.kind === 'image'
              ? img?.getAttribute('src') ?? undefined
              : props.kind === 'button' || props.kind === 'link'
                ? {
                      label: target?.innerText?.trim() ?? '',
                      href: link?.getAttribute('href') ?? '',
                      download: link?.hasAttribute('download') ? (link.getAttribute('download') || true) : undefined,
                  }
                : undefined;

    let defaultValue: unknown = resolved.value || props.defaultValue || fromDom;
    if ((props.kind === 'button' || props.kind === 'link') && fromDom) {
        const current = asButton(defaultValue, { label: '', href: '' });
        const dom = asButton(fromDom, { label: '', href: '' });
        defaultValue = {
            label: current.label || dom.label,
            href: current.href || dom.href,
            target: current.target || dom.target,
            download: current.download ?? dom.download,
        };
    }

    return {
        fieldKey: props.fieldKey,
        kind: props.kind,
        label: prettyLabel(props.fieldKey, props.label),
        defaultValue: cloneValue(defaultValue),
    };
}

function cloneValue(value: unknown): unknown {
    try {
        return value === undefined ? undefined : JSON.parse(JSON.stringify(value));
    } catch {
        return null;
    }
}

function openEditor(event: MouseEvent): void {
    if (!siteCms.value.enabled) {
        return;
    }
    event.preventDefault();
    event.stopPropagation();
    postToParent({ source: SITE_CMS_SOURCE, type: 'select', field: fieldFrom(shell.value) });
}

function onShellClick(event: MouseEvent): void {
    if (!siteCms.value.enabled) {
        return;
    }
    const target = event.target as HTMLElement | null;
    if (target?.closest('a')) {
        event.preventDefault();
    }
}

function onMessage(event: MessageEvent): void {
    if (event.origin !== window.location.origin || !isSiteCmsMessage(event.data)) {
        return;
    }
    if (event.data.type !== 'active') {
        return;
    }
    if (!event.data.fieldKey) {
        editorUi.setActive(null);
        return;
    }
    if (event.data.fieldKey === props.fieldKey) {
        editorUi.setActive(fieldFrom(shell.value));
    } else if (editorUi.active?.fieldKey === props.fieldKey) {
        editorUi.setActive(null);
    }
}

onMounted(() => {
    window.addEventListener('message', onMessage);
});
onUnmounted(() => {
    window.removeEventListener('message', onMessage);
});
</script>

<template>
    <span
        ref="shell"
        class="scms-shell"
        :class="{
            'scms-shell--on': siteCms.enabled,
            'scms-shell--active': active,
            'scms-shell--block': block,
        }"
        @click="onShellClick"
    >
        <slot />
        <button
            v-if="siteCms.enabled"
            class="scms-pencil"
            type="button"
            :aria-label="`Edit ${prettyLabel(fieldKey, label)}`"
            @click="openEditor"
        >
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path
                    d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
        </button>
    </span>
</template>
