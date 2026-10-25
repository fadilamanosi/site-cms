<script setup lang="ts">
import { prettyLabel } from '../fieldValue';
import type { ActiveField } from '../types';
import SiteCmsFieldForm from './SiteCmsFieldForm.vue';

defineProps<{
    field: ActiveField;
    pageKey: string;
}>();

const emit = defineEmits<{
    close: [];
    saved: [value: unknown];
}>();
</script>

<template>
    <aside class="scms-inspector" aria-label="Field editor">
        <header class="scms-inspector-h">
            <span>{{ prettyLabel(field.fieldKey, field.label) }}</span>
            <button class="scms-btn" type="button" aria-label="Close editor" @click="emit('close')">×</button>
        </header>
        <div class="scms-inspector-body">
            <SiteCmsFieldForm :key="field.fieldKey" :field="field" :page-key="pageKey" @saved="(value) => emit('saved', value)" />
        </div>
    </aside>
</template>
