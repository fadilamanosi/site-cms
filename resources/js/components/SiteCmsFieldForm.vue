<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from 'vue';
import { asButton, asGallery, asImage, asText } from '../fieldValue';
import { useSiteCms } from '../composables/useSiteCms';
import { useSiteCmsDraft } from '../composables/useSiteCmsDraft';
import type { ActiveField, GalleryItem, SiteCmsKind } from '../types';
import SiteCmsGalleryField from './SiteCmsGalleryField.vue';
import SiteCmsImageField from './SiteCmsImageField.vue';
import SiteCmsLinkField from './SiteCmsLinkField.vue';

const props = defineProps<{
    field: ActiveField;
    pageKey?: string;
}>();

const emit = defineEmits<{
    saved: [value: unknown];
}>();

const { siteCms } = useSiteCms();
const { save, saving, error } = useSiteCmsDraft();
const saved = ref(false);
let savedTimer = 0;

const inputText = ref('');
const inputImage = ref('');
const inputAlt = ref('');
const inputLabel = ref('');
const inputHref = ref('');
const inputTarget = ref('');
const inputDownload = ref<boolean | string>(false);
const inputColor = ref('#000000');
const inputIcon = ref('');
const inputItems = ref<GalleryItem[]>([]);

const kind = computed(() => props.field.kind);
const colorSwatch = computed(() => (/^#[0-9a-fA-F]{6}$/.test(inputColor.value) ? inputColor.value : '#000000'));

function currentRaw(): unknown {
    return siteCms.value.values[props.field.fieldKey]?.value ?? props.field.defaultValue;
}

watch(
    () => [props.field.fieldKey, siteCms.value.values],
    () => {
        const raw = currentRaw();
        inputText.value = asText(raw, typeof raw === 'string' ? raw : '');
        const image = asImage(raw, '', '');
        inputImage.value = image.src;
        inputAlt.value = image.alt;
        const button = asButton(raw, { label: '', href: '' });
        inputLabel.value = button.label;
        inputHref.value = button.href;
        inputTarget.value = button.target ?? '';
        inputDownload.value = button.download ?? false;
        inputColor.value = asText(raw, '#000000') || '#000000';
        inputIcon.value = asText(raw, '');
        inputItems.value = asGallery(raw, Array.isArray(raw) ? (raw as GalleryItem[]) : []).items;
    },
    { immediate: true, deep: true },
);

function payloadValue(next: SiteCmsKind): unknown {
    if (next === 'button' || next === 'link') {
        return {
            label: inputLabel.value,
            href: inputHref.value,
            ...(inputTarget.value && !inputDownload.value ? { target: inputTarget.value } : {}),
            ...(inputDownload.value ? { download: inputDownload.value } : {}),
        };
    }
    if (next === 'image') {
        return { value: inputImage.value, alt: inputAlt.value };
    }
    if (next === 'gallery') {
        return { items: inputItems.value };
    }
    if (next === 'color') {
        return inputColor.value;
    }
    if (next === 'icon') {
        return inputIcon.value;
    }

    return inputText.value;
}

async function submit(): Promise<void> {
    const value = payloadValue(kind.value);
    const pageKey = props.pageKey || siteCms.value.pageKey || 'home';
    const ok = await save(pageKey, props.field.fieldKey, kind.value, value);
    if (ok) {
        saved.value = true;
        window.clearTimeout(savedTimer);
        savedTimer = window.setTimeout(() => {
            saved.value = false;
        }, 1200);
        emit('saved', value);
    }
}

onUnmounted(() => {
    window.clearTimeout(savedTimer);
});
</script>

<template>
    <form class="scms-form" novalidate @submit.prevent="submit">
        <label v-if="kind === 'text'" class="scms-field">
            <span>Text</span>
            <textarea v-model="inputText" rows="5" />
        </label>
        <SiteCmsImageField
            v-else-if="kind === 'image'"
            v-model="inputImage"
            v-model:alt="inputAlt"
            with-alt
            :label="field.label"
        />
        <SiteCmsLinkField
            v-else-if="kind === 'button' || kind === 'link'"
            v-model:label="inputLabel"
            v-model:href="inputHref"
            v-model:target="inputTarget"
            v-model:download="inputDownload"
        />
        <SiteCmsGalleryField v-else-if="kind === 'gallery'" v-model="inputItems" />
        <label v-else-if="kind === 'color'" class="scms-field">
            <span>Color</span>
            <input
                :value="colorSwatch"
                type="color"
                @input="inputColor = ($event.target as HTMLInputElement).value"
            />
            <input v-model="inputColor" type="text" />
        </label>
        <label v-else-if="kind === 'icon'" class="scms-field">
            <span>Icon name</span>
            <input v-model="inputIcon" type="text" />
        </label>
        <label v-else class="scms-field">
            <span>{{ kind }}</span>
            <input v-model="inputText" type="text" />
        </label>
        <p v-if="error" class="scms-error">{{ error }}</p>
        <button
            class="scms-btn scms-btn-primary"
            :class="{ 'scms-btn--saved': saved }"
            type="submit"
            :disabled="saving"
        >
            {{ saving ? 'Saving…' : saved ? 'Saved' : 'Update' }}
        </button>
    </form>
</template>
