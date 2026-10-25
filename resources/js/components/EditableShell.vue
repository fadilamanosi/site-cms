<script setup lang="ts">
import { computed } from 'vue';
import { editorUi } from '../editorUi';
import { useSiteCms } from '../composables/useSiteCms';
import type { SiteCmsKind } from '../types';

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

const active = computed(
    () => siteCms.value.enabled && editorUi.active?.fieldKey === props.fieldKey,
);

function onClick(event: MouseEvent): void {
    if (!siteCms.value.enabled) {
        return;
    }
    event.preventDefault();
    event.stopPropagation();
    const target = event.currentTarget as HTMLElement | null;
    const img = target?.querySelector('img');
    editorUi.setActive({
        fieldKey: props.fieldKey,
        kind: props.kind,
        label: props.label || props.fieldKey,
        defaultValue:
            props.defaultValue ||
            (props.kind === 'text' ? target?.innerText?.trim() : undefined) ||
            (props.kind === 'image' ? img?.getAttribute('src') ?? undefined : undefined),
    });
}
</script>

<template>
    <span
        class="scms-shell"
        :class="{ 'scms-shell--on': siteCms.enabled, 'scms-shell--active': active }"
        @click.capture="onClick"
    >
        <slot />
    </span>
</template>
