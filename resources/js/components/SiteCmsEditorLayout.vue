<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import SiteCmsControlsPanel from './SiteCmsControlsPanel.vue';
import { useSiteCms } from '../composables/useSiteCms';
import { editorUi } from '../editorUi';
import '../css/site-cms.css';

const { siteCms } = useSiteCms();

function saveDraft(): void {
    window.dispatchEvent(new Event('site-cms:save'));
}

function publish(): void {
    router.post(
        siteCms.value.routes.publish,
        { page_key: siteCms.value.pageKey },
        { preserveScroll: true },
    );
}

function exit(): void {
    editorUi.setActive(null);
    router.post(siteCms.value.routes.exit);
}

function openPage(pageKey: string): void {
    router.post(siteCms.value.routes.enter, { page_key: pageKey });
}
</script>

<template>
    <div class="scms-app">
        <header class="scms-bar">
            <h1>Site CMS</h1>
            <div class="scms-actions">
                <button class="scms-btn" type="button" @click="editorUi.rightOpen = !editorUi.rightOpen">
                    Panel
                </button>
                <button class="scms-btn" type="button" @click="saveDraft">Save draft</button>
                <button class="scms-btn scms-btn-primary" type="button" @click="publish">Publish</button>
                <button class="scms-btn" type="button" @click="exit">Exit</button>
            </div>
        </header>
        <div class="scms-shell-grid">
            <aside class="scms-nav">
                <button class="link" type="button" @click="router.visit(siteCms.routes.dashboard)">
                    Site settings
                </button>
                <button
                    v-for="page in siteCms.pages"
                    :key="page.key"
                    class="link"
                    :class="{ active: siteCms.pageKey === page.key }"
                    type="button"
                    @click="openPage(page.key)"
                >
                    {{ page.label }}
                </button>
            </aside>
            <div class="scms-main">
                <slot />
            </div>
            <aside v-if="editorUi.rightOpen" class="scms-panel">
                <SiteCmsControlsPanel />
            </aside>
        </div>
    </div>
</template>
