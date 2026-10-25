<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useSiteCms } from '../../composables/useSiteCms';
import '../../../css/site-cms.css';

const { siteCms } = useSiteCms();

const title = ref(siteCms.value.site.title ?? '');
const description = ref(siteCms.value.site.description ?? '');
const logo = ref(siteCms.value.site.logo ?? '');
const image = ref(siteCms.value.site.image ?? '');
const favicon = ref(siteCms.value.site.favicon ?? '');

function save(): void {
    router.put(
        siteCms.value.routes.draft,
        {
            page_key: 'site',
            fields: [
                { field_key: 'site.title', kind: 'text', value: title.value },
                { field_key: 'site.description', kind: 'text', value: description.value },
                { field_key: 'site.logo', kind: 'image', value: logo.value },
                { field_key: 'site.image', kind: 'image', value: image.value },
                { field_key: 'site.favicon', kind: 'image', value: favicon.value },
            ],
        },
        { preserveScroll: true },
    );
}

function publish(): void {
    router.post(siteCms.value.routes.publish, { page_key: 'site' }, { preserveScroll: true });
}

function editHome(): void {
    router.post(siteCms.value.routes.enter, { page_key: 'home' });
}
</script>

<template>
    <Head title="Site settings" />
    <div class="scms-app">
        <header class="scms-bar">
            <h1>Site settings</h1>
            <div class="scms-actions">
                <button class="scms-btn" type="button" @click="save">Save draft</button>
                <button class="scms-btn scms-btn-primary" type="button" @click="publish">Publish</button>
                <button class="scms-btn" type="button" @click="editHome">Edit pages</button>
                <button class="scms-btn" type="button" @click="router.post('/staff/login/logout')">Log out</button>
            </div>
        </header>
        <div class="scms-card" style="margin: 2rem auto">
            <label class="scms-field">
                <span>Title</span>
                <input v-model="title" type="text" />
            </label>
            <label class="scms-field">
                <span>Description</span>
                <textarea v-model="description" rows="4" />
            </label>
            <label class="scms-field">
                <span>Logo URL</span>
                <input v-model="logo" type="text" />
            </label>
            <label class="scms-field">
                <span>Favicon URL</span>
                <input v-model="favicon" type="text" />
            </label>
            <label class="scms-field">
                <span>Social image URL</span>
                <input v-model="image" type="text" />
            </label>
        </div>
    </div>
</template>
