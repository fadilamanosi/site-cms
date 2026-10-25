<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import SiteCmsImageField from '../../components/SiteCmsImageField.vue';
import SiteCmsInspector from '../../components/SiteCmsInspector.vue';
import SiteCmsMediaModal from '../../components/SiteCmsMediaModal.vue';
import { useSiteCms } from '../../composables/useSiteCms';
import { asImage, asText } from '../../fieldValue';
import { applyHostColorScheme } from '../../hostTheme';
import { mediaUi } from '../../mediaUi';
import { isSiteCmsMessage, postToFrame, previewUrl, SITE_CMS_SOURCE } from '../../previewBridge';
import type { ActiveField, SiteCmsPage } from '../../types';
import '../../../css/site-cms.css';

const props = defineProps<{
    editorPage: SiteCmsPage;
}>();

const { siteCms } = useSiteCms();
const root = ref<HTMLElement | null>(null);
const frame = ref<HTMLIFrameElement | null>(null);
const page = ref<SiteCmsPage>(props.editorPage);
const active = ref<ActiveField | null>(null);
const open = ref({ identity: true, seo: false, pages: false });
const frameTick = ref(0);
const previewScroll = ref<{ x: number; y: number } | null>(null);
const canvasSrc = computed(() => page.value.preview_url || previewUrl(page.value.path));

const title = ref(siteCms.value.site.title ?? '');
const description = ref(siteCms.value.site.description ?? '');
const logo = ref(siteCms.value.site.logo ?? '');
const image = ref(siteCms.value.site.image ?? '');
const favicon = ref(siteCms.value.site.favicon ?? '');

function toggle(key: 'identity' | 'seo' | 'pages'): void {
    open.value[key] = !open.value[key];
}

function capturePreviewScroll(): void {
    const win = frame.value?.contentWindow;
    if (!win) {
        return;
    }
    previewScroll.value = { x: win.scrollX, y: win.scrollY };
}

function restorePreviewScroll(): void {
    const next = previewScroll.value;
    previewScroll.value = null;
    if (!next) {
        return;
    }
    const win = frame.value?.contentWindow;
    const root = frame.value?.contentDocument?.documentElement;
    if (!win || !root) {
        return;
    }
    root.style.scrollBehavior = 'auto';
    const apply = () => win.scrollTo(next.x, next.y);
    apply();
    win.requestAnimationFrame(apply);
}

function reloadPreview(): void {
    capturePreviewScroll();
    frameTick.value += 1;
}

function onFrameLoad(): void {
    restorePreviewScroll();
    syncFrame(active.value?.fieldKey ?? null);
}

function syncFrame(fieldKey: string | null): void {
    postToFrame(frame.value, {
        source: SITE_CMS_SOURCE,
        type: 'active',
        fieldKey,
    });
}

function clearField(): void {
    active.value = null;
    syncFrame(null);
}

function selectField(field: ActiveField): void {
    if (active.value?.fieldKey === field.fieldKey) {
        clearField();
        return;
    }
    active.value = field;
    syncFrame(field.fieldKey);
}

function openPage(next: SiteCmsPage): void {
    page.value = next;
    clearField();
    open.value.pages = true;
    previewScroll.value = null;
    frameTick.value += 1;
}

const identityDirty = computed(() =>
    title.value !== (siteCms.value.site.title ?? '')
    || description.value !== (siteCms.value.site.description ?? '')
    || logo.value !== (siteCms.value.site.logo ?? '')
    || image.value !== (siteCms.value.site.image ?? '')
    || favicon.value !== (siteCms.value.site.favicon ?? ''),
);
const draftDirty = computed(() => identityDirty.value || siteCms.value.draft.dirty);
const draftCount = computed(() => siteCms.value.draft.count + (identityDirty.value ? 1 : 0));
const canPublish = computed(() => draftDirty.value);

function identityFields() {
    return [
        { field_key: 'site.title', kind: 'text', value: title.value },
        { field_key: 'site.description', kind: 'text', value: description.value },
        { field_key: 'site.logo', kind: 'image', value: logo.value },
        { field_key: 'site.image', kind: 'image', value: image.value },
        { field_key: 'site.favicon', kind: 'image', value: favicon.value },
    ];
}

function saveDraft(): void {
    if (!identityDirty.value && !siteCms.value.draft.dirty) {
        return;
    }
    router.put(
        siteCms.value.routes.draft,
        { page_key: 'site', fields: identityFields() },
        { preserveScroll: true, preserveState: true, onSuccess: reloadPreview },
    );
}

function publishNow(): void {
    router.post(
        siteCms.value.routes.publish,
        { page_key: page.value.key },
        { preserveScroll: true, preserveState: true, onSuccess: reloadPreview },
    );
}

function publish(): void {
    if (!canPublish.value) {
        return;
    }
    if (identityDirty.value) {
        router.put(
            siteCms.value.routes.draft,
            { page_key: 'site', fields: identityFields() },
            { preserveScroll: true, preserveState: true, onSuccess: publishNow },
        );
        return;
    }
    publishNow();
}

function exit(): void {
    router.post(siteCms.value.routes.exit);
}

function applySaved(field: ActiveField | null, value: unknown): void {
    if (!field) {
        return;
    }
    const key = field.fieldKey;
    if (key === 'site.title') {
        title.value = asText(value, title.value);
    }
    if (key === 'site.description') {
        description.value = asText(value, description.value);
    }
    if (key === 'site.logo') {
        logo.value = asImage(value, logo.value).src;
    }
    if (key === 'site.image') {
        image.value = asImage(value, image.value).src;
    }
    if (key === 'site.favicon') {
        favicon.value = asImage(value, favicon.value).src;
    }
    postToFrame(frame.value, {
        source: SITE_CMS_SOURCE,
        type: 'patch',
        fieldKey: key,
        kind: field.kind,
        value,
    });
}

function onMessage(event: MessageEvent): void {
    if (event.origin !== window.location.origin || !isSiteCmsMessage(event.data)) {
        return;
    }
    if (event.data.type !== 'select') {
        return;
    }
    selectField(event.data.field);
}

function onKey(event: KeyboardEvent): void {
    if (event.key === 'Escape' && active.value && !mediaUi.open) {
        clearField();
    }
}

onMounted(() => {
    applyHostColorScheme(root.value);
    window.addEventListener('message', onMessage);
    document.addEventListener('keydown', onKey);
});
onUnmounted(() => {
    window.removeEventListener('message', onMessage);
    document.removeEventListener('keydown', onKey);
});
</script>

<template>
    <Head title="Customize" />
    <div ref="root" class="scms-editor">
        <header class="scms-top">
            <h1>{{ page.label }}</h1>
            <div class="scms-actions">
                <button
                    class="scms-btn scms-btn-draft"
                    type="button"
                    :class="{ 'is-on': draftDirty }"
                    :disabled="!identityDirty && !siteCms.draft.dirty"
                    :title="draftDirty ? 'Unpublished changes' : 'No draft changes'"
                    :aria-pressed="draftDirty"
                    @click="saveDraft"
                >
                    Save to draft
                    <span v-if="draftDirty" class="scms-pip" :aria-label="`${draftCount} unpublished`" />
                </button>
                <button
                    class="scms-btn"
                    :class="{ 'scms-btn-primary': canPublish }"
                    type="button"
                    :disabled="!canPublish"
                    :title="canPublish ? 'Publish unpublished changes' : 'Nothing to publish'"
                    @click="publish"
                >
                    Publish
                </button>
                <button class="scms-btn" type="button" @click="mediaUi.show()">Media</button>
                <button class="scms-btn" type="button" @click="exit">Exit</button>
                <button
                    v-if="siteCms.routes.logout"
                    class="scms-btn"
                    type="button"
                    @click="router.post(siteCms.routes.logout)"
                >
                    Log out
                </button>
            </div>
        </header>
        <div class="scms-body">
            <aside class="scms-rail">
                <button
                    class="scms-section-h"
                    type="button"
                    :aria-expanded="open.identity"
                    @click="toggle('identity')"
                >
                    Site identity
                    <svg
                        class="scms-chevron"
                        :class="{ 'scms-chevron--up': open.identity }"
                        viewBox="0 0 12 12"
                        aria-hidden="true"
                    >
                        <path d="M2.5 4.25 L6 7.75 L9.5 4.25" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <Transition name="scms-fold">
                    <div v-if="open.identity" class="scms-section">
                        <label class="scms-field">
                            <span>Title</span>
                            <input v-model="title" type="text" />
                        </label>
                        <SiteCmsImageField v-model="logo" label="Logo" />
                        <SiteCmsImageField v-model="favicon" label="Favicon" />
                    </div>
                </Transition>
                <button
                    class="scms-section-h"
                    type="button"
                    :aria-expanded="open.seo"
                    @click="toggle('seo')"
                >
                    SEO
                    <svg
                        class="scms-chevron"
                        :class="{ 'scms-chevron--up': open.seo }"
                        viewBox="0 0 12 12"
                        aria-hidden="true"
                    >
                        <path d="M2.5 4.25 L6 7.75 L9.5 4.25" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <Transition name="scms-fold">
                    <div v-if="open.seo" class="scms-section">
                        <label class="scms-field">
                            <span>Description</span>
                            <textarea v-model="description" rows="4" />
                        </label>
                        <SiteCmsImageField v-model="image" label="Social image" />
                    </div>
                </Transition>
                <button
                    class="scms-section-h"
                    type="button"
                    :aria-expanded="open.pages"
                    @click="toggle('pages')"
                >
                    Pages
                    <svg
                        class="scms-chevron"
                        :class="{ 'scms-chevron--up': open.pages }"
                        viewBox="0 0 12 12"
                        aria-hidden="true"
                    >
                        <path d="M2.5 4.25 L6 7.75 L9.5 4.25" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </button>
                <Transition name="scms-fold">
                    <div v-if="open.pages" class="scms-section">
                        <div class="scms-pages">
                            <button
                                v-for="item in siteCms.pages"
                                :key="item.key"
                                type="button"
                                :class="{ active: page.key === item.key }"
                                @click="openPage(item)"
                            >
                                {{ item.label }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </aside>
            <div class="scms-canvas">
                <iframe
                    :key="`${canvasSrc}:${frameTick}`"
                    ref="frame"
                    :src="canvasSrc"
                    title="Live preview"
                    @load="onFrameLoad"
                />
            </div>
            <Transition name="scms-inspect">
                <SiteCmsInspector
                    v-if="active"
                    :field="active"
                    :page-key="page.key"
                    @close="clearField"
                    @saved="(value) => applySaved(active, value)"
                />
            </Transition>
        </div>
        <SiteCmsMediaModal />
    </div>
</template>
