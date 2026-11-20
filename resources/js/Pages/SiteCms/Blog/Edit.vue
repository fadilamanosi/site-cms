<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import SiteCmsDeskNav from '../../../components/SiteCmsDeskNav.vue';
import SiteCmsImageField from '../../../components/SiteCmsImageField.vue';
import SiteCmsMediaModal from '../../../components/SiteCmsMediaModal.vue';
import SiteCmsRichTextEditor from '../../../components/SiteCmsRichTextEditor.vue';
import { useSiteCms } from '../../../composables/useSiteCms';
import { applyHostColorScheme } from '../../../hostTheme';
import { mediaUi } from '../../../mediaUi';
import type { BlogPostCard } from '../../../types';
import '../../../../css/site-cms.css';

const props = defineProps<{
    post: BlogPostCard;
    isNew: boolean;
}>();

const { siteCms } = useSiteCms();
const root = ref<HTMLElement | null>(null);
const seoOpen = ref(false);
const moreOpen = ref(false);

const form = useForm({
    title: props.post.title ?? '',
    label: props.post.label ?? '',
    slug: props.post.slug ?? '',
    excerpt: props.post.excerpt ?? '',
    body: props.post.body ?? '',
    cover_src: props.post.cover?.src ?? '',
    cover_alt: props.post.cover?.alt ?? '',
    publish: false,
    tags: (props.post.tags ?? []).join(', '),
    seo_title: props.post.seo?.title ?? '',
    seo_description: props.post.seo?.description ?? '',
    seo_image: props.post.seo?.image ?? '',
    published_at: props.post.publishedAt ? props.post.publishedAt.slice(0, 16) : '',
});

watch(
    () => props.post,
    (next) => {
        form.title = next.title ?? '';
        form.label = next.label ?? '';
        form.slug = next.slug ?? '';
        form.excerpt = next.excerpt ?? '';
        form.body = next.body ?? '';
        form.cover_src = next.cover?.src ?? '';
        form.cover_alt = next.cover?.alt ?? '';
        form.publish = false;
        form.tags = (next.tags ?? []).join(', ');
        form.seo_title = next.seo?.title ?? '';
        form.seo_description = next.seo?.description ?? '';
        form.seo_image = next.seo?.image ?? '';
        form.published_at = next.publishedAt ? next.publishedAt.slice(0, 16) : '';
        form.clearErrors();
    },
);

const heading = computed(() => (props.isNew ? 'Add new post' : 'Edit post'));
const isLive = computed(() => props.post.status === 'published');
const hasPending = computed(() => Boolean(props.post.hasUnpublishedChanges));
const statusLabel = computed(() => {
    if (!isLive.value) {
        return 'Draft — not on the public site';
    }
    if (hasPending.value) {
        return 'Published — unpublished edits pending';
    }

    return 'Published — public site matches this draft';
});

function withId(template: string | null, id: number | null | undefined): string | null {
    if (!template || !id) {
        return null;
    }

    return template.replace('{id}', String(id));
}

function save(andPublish = false): void {
    form.publish = andPublish;

    if (props.isNew) {
        const url = siteCms.value.routes.blogStore;
        if (!url) {
            return;
        }
        form.post(url, { preserveScroll: true });
        return;
    }

    const url = withId(siteCms.value.routes.blogUpdate, props.post.id);
    if (!url) {
        return;
    }
    form.put(url, { preserveScroll: true });
}

function discardDraft(): void {
    moreOpen.value = false;
    if (!props.post.id || !window.confirm('Discard unpublished edits and restore the live published version?')) {
        return;
    }
    const url = withId(siteCms.value.routes.blogDiscard, props.post.id);
    if (url) {
        router.post(url, {}, { preserveScroll: true });
    }
}

function togglePublish(): void {
    moreOpen.value = false;
    if (!props.post.id) {
        return;
    }
    if (props.post.status === 'published') {
        const url = withId(siteCms.value.routes.blogUnpublish, props.post.id);
        if (url) {
            router.post(url, {}, { preserveScroll: true });
        }
        return;
    }
    save(true);
}

function destroy(): void {
    moreOpen.value = false;
    if (!props.post.id || !window.confirm('Delete this post permanently?')) {
        return;
    }
    const url = withId(siteCms.value.routes.blogDestroy, props.post.id);
    if (url) {
        router.delete(url);
    }
}

function exit(): void {
    router.post(siteCms.value.routes.exit);
}

function viewPublic(): void {
    moreOpen.value = false;
    if (props.post.href) {
        window.open(props.post.href, '_blank', 'noopener');
    }
}

onMounted(() => {
    applyHostColorScheme(root.value);
});
</script>

<template>
    <Head :title="heading" />
    <div ref="root" class="scms-editor scms-blog-desk">
        <header class="scms-top scms-top--edit">
            <div class="scms-top-lead">
                <SiteCmsDeskNav active="blog" />
                <h1>{{ heading }}</h1>
            </div>
            <div class="scms-actions">
                <button
                    class="scms-btn scms-btn-primary"
                    type="button"
                    :disabled="form.processing"
                    @click="save(false)"
                >
                    {{ form.processing && !form.publish ? 'Saving…' : 'Save draft' }}
                </button>
                <button
                    class="scms-btn"
                    type="button"
                    :disabled="form.processing"
                    @click="save(true)"
                >
                    {{ form.processing && form.publish ? 'Publishing…' : isLive ? 'Publish changes' : 'Publish' }}
                </button>
                <button
                    v-if="isLive"
                    class="scms-btn"
                    type="button"
                    :disabled="form.processing"
                    @click="togglePublish"
                >
                    Unpublish
                </button>
                <Link
                    v-if="siteCms.routes.blogIndex"
                    class="scms-btn"
                    :href="siteCms.routes.blogIndex"
                >
                    All posts
                </Link>
                <div class="scms-more">
                    <button
                        class="scms-btn"
                        type="button"
                        :aria-expanded="moreOpen"
                        @click="moreOpen = !moreOpen"
                    >
                        More
                    </button>
                    <div v-if="moreOpen" class="scms-more-menu" role="menu">
                        <button
                            v-if="!isNew && post.href && isLive"
                            class="scms-more-item"
                            type="button"
                            role="menuitem"
                            @click="viewPublic"
                        >
                            View live post
                        </button>
                        <button
                            v-if="!isNew && isLive && hasPending"
                            class="scms-more-item"
                            type="button"
                            role="menuitem"
                            @click="discardDraft"
                        >
                            Discard draft edits
                        </button>
                        <button
                            class="scms-more-item"
                            type="button"
                            role="menuitem"
                            @click="moreOpen = false; mediaUi.show()"
                        >
                            Media library
                        </button>
                        <button
                            v-if="!isNew"
                            class="scms-more-item"
                            type="button"
                            role="menuitem"
                            @click="destroy"
                        >
                            Delete
                        </button>
                        <button
                            class="scms-more-item"
                            type="button"
                            role="menuitem"
                            @click="moreOpen = false; exit()"
                        >
                            Exit editor
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <div class="scms-blog-edit">
            <div class="scms-blog-main">
                <label class="scms-field scms-blog-title-field">
                    <span class="scms-sr">Title</span>
                    <input v-model="form.title" type="text" placeholder="Post title" class="scms-blog-title-input" />
                </label>
                <p v-if="form.errors.title" class="scms-error">{{ form.errors.title }}</p>

                <SiteCmsRichTextEditor v-model="form.body" />
                <p v-if="form.errors.body" class="scms-error">{{ form.errors.body }}</p>
            </div>

            <aside class="scms-blog-meta">
                <section class="scms-blog-card">
                    <h2>Status</h2>
                    <p class="scms-muted" style="margin: 0; font-size: 0.85rem">{{ statusLabel }}</p>
                    <p class="scms-muted" style="margin: 0.45rem 0 0; font-size: 0.8rem">
                        Save draft updates only the editor copy. Publish pushes it to the public site.
                    </p>
                    <label class="scms-field" style="margin-top: 0.75rem">
                        <span>Publish date</span>
                        <input v-model="form.published_at" type="datetime-local" />
                    </label>
                </section>

                <section class="scms-blog-card">
                    <h2>Label</h2>
                    <label class="scms-field">
                        <span class="scms-sr">Label</span>
                        <input
                            v-model="form.label"
                            type="text"
                            placeholder="e.g. Personal reflection"
                        />
                    </label>
                    <p class="scms-muted" style="margin: 0.35rem 0 0; font-size: 0.8rem">Shown above the title on the public site.</p>
                    <p v-if="form.errors.label" class="scms-error">{{ form.errors.label }}</p>
                </section>

                <section class="scms-blog-card">
                    <h2>Permalink</h2>
                    <label class="scms-field">
                        <span>Slug</span>
                        <input v-model="form.slug" type="text" placeholder="auto from title" />
                    </label>
                    <p v-if="form.errors.slug" class="scms-error">{{ form.errors.slug }}</p>
                    <p v-if="isLive" class="scms-muted" style="margin: 0.35rem 0 0; font-size: 0.8rem">
                        Slug changes go live only when you publish.
                    </p>
                </section>

                <section class="scms-blog-card">
                    <h2>Excerpt</h2>
                    <label class="scms-field">
                        <span class="scms-sr">Excerpt</span>
                        <textarea v-model="form.excerpt" rows="4" placeholder="Short summary for listings" />
                    </label>
                </section>

                <section class="scms-blog-card">
                    <h2>Featured image</h2>
                    <SiteCmsImageField v-model="form.cover_src" v-model:alt="form.cover_alt" with-alt label="Cover" />
                </section>

                <section class="scms-blog-card">
                    <h2>Tags</h2>
                    <label class="scms-field">
                        <span class="scms-sr">Tags</span>
                        <input v-model="form.tags" type="text" placeholder="news, archive" />
                    </label>
                </section>

                <section class="scms-blog-card">
                    <button class="scms-section-h" type="button" :aria-expanded="seoOpen" @click="seoOpen = !seoOpen">
                        SEO
                        <svg
                            class="scms-chevron"
                            :class="{ 'scms-chevron--up': seoOpen }"
                            viewBox="0 0 12 12"
                            aria-hidden="true"
                        >
                            <path d="M2.5 4.25 L6 7.75 L9.5 4.25" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                    <div v-if="seoOpen" class="scms-section" style="border: 0; padding: 0.65rem 0 0">
                        <label class="scms-field">
                            <span>SEO title</span>
                            <input v-model="form.seo_title" type="text" />
                        </label>
                        <label class="scms-field">
                            <span>SEO description</span>
                            <textarea v-model="form.seo_description" rows="3" />
                        </label>
                        <SiteCmsImageField v-model="form.seo_image" label="SEO image" />
                    </div>
                </section>
            </aside>
        </div>

        <SiteCmsMediaModal />
    </div>
</template>
