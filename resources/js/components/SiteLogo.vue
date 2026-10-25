<script setup lang="ts">
import { computed } from 'vue';
import EditableShell from './EditableShell.vue';
import { useSiteField } from '../composables/useSiteField';

const props = withDefaults(
    defineProps<{
        fallback: string;
        alt?: string;
        fieldKey?: string;
    }>(),
    { alt: '', fieldKey: 'site.logo' },
);

const { resolved } = useSiteField(
    () => props.fieldKey,
    'image',
    () => props.fallback,
);

const src = computed(() => String(resolved.value ?? props.fallback));
</script>

<template>
    <EditableShell :field-key="fieldKey" kind="image" label="Logo" :default-value="fallback">
        <img :src="src" :alt="alt" />
    </EditableShell>
</template>
