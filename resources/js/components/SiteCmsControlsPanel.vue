<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { editorUi } from '../editorUi';
import { useSiteCms } from '../composables/useSiteCms';
import type { SiteCmsKind } from '../types';

const { siteCms } = useSiteCms();
const csrf = computed(() => {
    const token = usePage().props.csrf_token;
    return typeof token === 'string' ? token : '';
});

const inputText = ref('');
const inputImage = ref('');
const inputHref = ref('');
const inputLabel = ref('');
const fileInput = ref<HTMLInputElement | null>(null);

const active = computed(() => editorUi.active);

function currentRaw(): unknown {
    if (!active.value) {
        return null;
    }
    return siteCms.value.values[active.value.fieldKey]?.value ?? active.value.defaultValue;
}

watch(
    () => [active.value?.fieldKey, siteCms.value.values],
    () => {
        const raw = currentRaw();
        if (raw && typeof raw === 'object' && raw !== null && 'value' in raw && !('label' in raw)) {
            const v = String((raw as { value: unknown }).value ?? '');
            inputText.value = v;
            inputImage.value = v;
        } else if (typeof raw === 'string') {
            inputText.value = raw;
            inputImage.value = raw;
        } else if (raw && typeof raw === 'object' && 'label' in raw) {
            const button = raw as { label?: string; href?: string };
            inputLabel.value = button.label ?? '';
            inputHref.value = button.href ?? '';
        } else {
            inputText.value = raw != null ? String(raw) : '';
            inputImage.value = inputText.value;
            inputLabel.value = '';
            inputHref.value = '';
        }
    },
    { immediate: true, deep: true },
);

function payloadValue(kind: SiteCmsKind): unknown {
    if (kind === 'button') {
        return { label: inputLabel.value, href: inputHref.value };
    }
    if (kind === 'image') {
        return inputImage.value;
    }
    return inputText.value;
}

function save(): void {
    if (!active.value) {
        return;
    }
    router.put(
        siteCms.value.routes.draft,
        {
            page_key: siteCms.value.pageKey,
            fields: [
                {
                    field_key: active.value.fieldKey,
                    kind: active.value.kind,
                    value: payloadValue(active.value.kind),
                },
            ],
        },
        { preserveScroll: true },
    );
}

function onSaveEvent(): void {
    save();
}

onMounted(() => {
    window.addEventListener('site-cms:save', onSaveEvent);
});

onUnmounted(() => {
    window.removeEventListener('site-cms:save', onSaveEvent);
});

async function upload(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) {
        return;
    }
    const body = new FormData();
    body.append('file', file);
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? csrf.value;
    const response = await fetch(siteCms.value.routes.resources, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            ...(token ? { 'X-CSRF-TOKEN': token } : {}),
        },
        credentials: 'same-origin',
        body,
    });
    const json = (await response.json()) as { data?: { url?: string } };
    if (json.data?.url) {
        inputImage.value = json.data.url;
    }
    input.value = '';
}
</script>

<template>
    <div>
        <h2>Edit</h2>
        <p v-if="!active" class="scms-muted">Click a marked field on the page.</p>
        <template v-else>
            <p class="scms-muted">{{ active.label }}</p>
            <label v-if="active.kind === 'text'" class="scms-field">
                <span>Text</span>
                <textarea v-model="inputText" rows="5" />
            </label>
            <label v-else-if="active.kind === 'image'" class="scms-field">
                <span>Image URL</span>
                <input v-model="inputImage" type="text" />
                <input ref="fileInput" type="file" accept="image/*" @change="upload" />
                <img v-if="inputImage" class="scms-preview" :src="inputImage" alt="" />
            </label>
            <template v-else-if="active.kind === 'button'">
                <label class="scms-field">
                    <span>Label</span>
                    <input v-model="inputLabel" type="text" />
                </label>
                <label class="scms-field">
                    <span>Link</span>
                    <input v-model="inputHref" type="text" />
                </label>
            </template>
            <label v-else class="scms-field">
                <span>{{ active.kind }}</span>
                <input v-model="inputText" type="text" />
            </label>
            <button class="scms-btn scms-btn-primary" type="button" @click="save">Save field</button>
        </template>
    </div>
</template>
