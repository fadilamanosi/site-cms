<script setup lang="ts">
import { computed } from 'vue';
import {
    fileNameFromHref,
    hrefDisplay,
    hrefStored,
    isRemoteHref,
    linkTypeFromHref,
    type LinkType,
} from '../fieldValue';
import { mediaUi } from '../mediaUi';

const label = defineModel<string>('label', { default: '' });
const href = defineModel<string>('href', { default: '' });
const target = defineModel<string>('target', { default: '' });
const download = defineModel<boolean | string>('download', { default: false });

const linkType = computed<LinkType>({
    get: () => linkTypeFromHref(href.value, download.value),
    set: (next) => {
        if (next === 'download') {
            download.value = download.value || true;
            target.value = '';
            return;
        }
        download.value = false;
        href.value = hrefStored(hrefDisplay(href.value, linkTypeFromHref(href.value, download.value)), next);
        if (next !== 'url') {
            target.value = '';
        }
    },
});

const hrefInput = computed({
    get: () => {
        if (linkType.value === 'download' && !isRemoteHref(href.value)) {
            return '';
        }

        return hrefDisplay(href.value, linkType.value);
    },
    set: (next) => {
        const value = next.trim();
        if (!value) {
            return;
        }
        href.value = hrefStored(value, linkType.value);
        if (linkType.value === 'download') {
            download.value = download.value || true;
        }
    },
});

const fileLabel = computed(() => {
    if (typeof download.value === 'string' && download.value !== '' && download.value !== 'true') {
        return download.value;
    }

    return fileNameFromHref(href.value);
});

const newTab = computed({
    get: () => target.value === '_blank',
    set: (checked) => {
        target.value = checked ? '_blank' : '';
    },
});

function pickFile(): void {
    mediaUi.show(href.value, (url, name) => {
        href.value = url;
        download.value = name || true;
        target.value = '';
    }, 'file');
}
</script>

<template>
    <label class="scms-field">
        <span>Label</span>
        <input v-model="label" type="text" />
    </label>
    <label class="scms-field">
        <span>Type</span>
        <select v-model="linkType">
            <option value="url">Link</option>
            <option value="email">Email</option>
            <option value="phone">Phone</option>
            <option value="download">Download</option>
        </select>
    </label>
    <template v-if="linkType === 'download'">
        <div class="scms-field">
            <span>File</span>
            <p v-if="fileLabel" class="scms-file-name">{{ fileLabel }}</p>
            <button class="scms-btn" type="button" @click="pickFile">{{ href ? 'Replace file' : 'Upload file' }}</button>
        </div>
        <label class="scms-field">
            <span>Or paste a file URL</span>
            <input v-model="hrefInput" type="text" inputmode="url" placeholder="/cv/file.pdf or https://…" autocomplete="off" />
        </label>
    </template>
    <label v-else class="scms-field">
        <span>{{ linkType === 'email' ? 'Email' : linkType === 'phone' ? 'Phone' : 'URL' }}</span>
        <input
            v-model="hrefInput"
            type="text"
            :inputmode="linkType === 'email' ? 'email' : linkType === 'phone' ? 'tel' : 'url'"
            :placeholder="linkType === 'email' ? 'name@example.com' : linkType === 'phone' ? '+234…' : 'https://… or #section'"
            autocomplete="off"
        />
    </label>
    <label v-if="linkType === 'url'" class="scms-check">
        <input v-model="newTab" type="checkbox" />
        <span>Open in a new tab</span>
    </label>
</template>
