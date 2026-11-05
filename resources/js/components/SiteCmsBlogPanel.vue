<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useSiteCms } from '../composables/useSiteCms';
import type { BlogPostCard } from '../types';
import SiteCmsImageField from './SiteCmsImageField.vue';

const { siteCms } = useSiteCms();
const open = ref(true);
const editingId = ref<number | 'new' | null>(null);
const saving = ref(false);
const error = ref('');

const form = ref({
    title: '',
    slug: '',
    excerpt: '',
    body: '',
    cover_src: '',
    cover_alt: '',
    status: 'draft' as 'draft' | 'published',
    tags: '',
});

const posts = computed(() => siteCms.value.blog?.posts ?? []);

function routeWithId(template: string | null, id: number): string | null {
    if (!template) {
        return null;
    }

    return template.replace('{id}', String(id));
}

function blank(): void {
    form.value = {
        title: '',
        slug: '',
        excerpt: '',
        body: '',
        cover_src: '',
        cover_alt: '',
        status: 'draft',
        tags: '',
    };
}

function startNew(): void {
    blank();
    editingId.value = 'new';
    error.value = '';
}

function startEdit(post: BlogPostCard): void {
    form.value = {
        title: post.title,
        slug: post.slug,
        excerpt: post.excerpt,
        body: post.body ?? '',
        cover_src: post.cover.src,
        cover_alt: post.cover.alt,
        status: post.status === 'published' ? 'published' : 'draft',
        tags: (post.tags ?? []).join(', '),
    };
    editingId.value = post.id;
    error.value = '';
}

function cancel(): void {
    editingId.value = null;
    error.value = '';
}

function payload() {
    return {
        title: form.value.title,
        slug: form.value.slug,
        excerpt: form.value.excerpt,
        body: form.value.body,
        cover_src: form.value.cover_src,
        cover_alt: form.value.cover_alt,
        status: form.value.status,
        tags: form.value.tags,
    };
}

function save(): void {
    error.value = '';
    saving.value = true;
    const done = {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            saving.value = false;
        },
        onSuccess: () => {
            editingId.value = null;
        },
        onError: (errors: Record<string, string>) => {
            error.value = Object.values(errors)[0] || 'Could not save post.';
        },
    };

    if (editingId.value === 'new') {
        const url = siteCms.value.routes.blogStore;
        if (!url) {
            saving.value = false;
            return;
        }
        router.post(url, payload(), done);
        return;
    }

    if (typeof editingId.value !== 'number') {
        saving.value = false;
        return;
    }

    const url = routeWithId(siteCms.value.routes.blogUpdate, editingId.value);
    if (!url) {
        saving.value = false;
        return;
    }
    router.put(url, payload(), done);
}

function publish(id: number): void {
    const url = routeWithId(siteCms.value.routes.blogPublish, id);
    if (!url) {
        return;
    }
    router.post(url, {}, { preserveScroll: true, preserveState: true });
}

function unpublish(id: number): void {
    const url = routeWithId(siteCms.value.routes.blogUnpublish, id);
    if (!url) {
        return;
    }
    router.post(url, {}, { preserveScroll: true, preserveState: true });
}

function destroy(id: number): void {
    const url = routeWithId(siteCms.value.routes.blogDestroy, id);
    if (!url) {
        return;
    }
    router.delete(url, { preserveScroll: true, preserveState: true });
}

watch(
    () => siteCms.value.blog?.posts,
    () => {
        if (editingId.value !== null && editingId.value !== 'new') {
            const still = posts.value.some((post) => post.id === editingId.value);
            if (!still) {
                editingId.value = null;
            }
        }
    },
);
</script>

<template>
    <div v-if="siteCms.blog?.enabled">
        <button
            class="scms-section-h"
            type="button"
            :aria-expanded="open"
            @click="open = !open"
        >
            Blog
            <svg class="scms-chevron" :class="{ 'scms-chevron--up': open }" viewBox="0 0 12 12" aria-hidden="true">
                <path d="M2.5 4.25 L6 7.75 L9.5 4.25" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
        <div v-if="open" class="scms-section">
            <div class="scms-gallery-tools" style="margin-top: 0">
                <button class="scms-btn scms-btn-primary" type="button" @click="startNew">New post</button>
            </div>

            <div v-if="editingId !== null" class="scms-gallery-item is-open" style="margin-top: 0.75rem">
                <div class="scms-gallery-body" style="border-top: 0; padding-top: 0.65rem">
                    <label class="scms-field">
                        <span>Title</span>
                        <input v-model="form.title" type="text" />
                    </label>
                    <label class="scms-field">
                        <span>Slug</span>
                        <input v-model="form.slug" type="text" placeholder="auto from title" />
                    </label>
                    <label class="scms-field">
                        <span>Excerpt</span>
                        <textarea v-model="form.excerpt" rows="3" />
                    </label>
                    <label class="scms-field">
                        <span>Body</span>
                        <textarea v-model="form.body" rows="8" />
                    </label>
                    <SiteCmsImageField v-model="form.cover_src" v-model:alt="form.cover_alt" with-alt label="Cover" />
                    <label class="scms-field">
                        <span>Tags (comma-separated)</span>
                        <input v-model="form.tags" type="text" placeholder="archive, news" />
                    </label>
                    <label class="scms-field">
                        <span>Status</span>
                        <select v-model="form.status">
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                    </label>
                    <p v-if="error" class="scms-error">{{ error }}</p>
                    <div class="scms-gallery-tools">
                        <button class="scms-btn scms-btn-primary" type="button" :disabled="saving" @click="save">
                            {{ saving ? 'Saving…' : 'Save post' }}
                        </button>
                        <button class="scms-btn" type="button" @click="cancel">Cancel</button>
                    </div>
                </div>
            </div>

            <div v-for="post in posts" :key="post.id" class="scms-gallery-item" style="margin-top: 0.55rem">
                <div class="scms-gallery-body" style="border-top: 0; padding: 0.55rem 0.65rem">
                    <p style="margin: 0 0 0.35rem; font-size: 0.85rem; font-weight: 600">{{ post.title }}</p>
                    <p class="scms-muted" style="margin: 0 0 0.45rem; font-size: 0.72rem">
                        {{ post.status === 'published' ? 'Published' : 'Draft' }}
                        <template v-if="post.tags?.length"> · {{ post.tags.join(', ') }}</template>
                    </p>
                    <div class="scms-gallery-tools" style="margin: 0">
                        <button class="scms-btn" type="button" @click="startEdit(post)">Edit</button>
                        <button
                            v-if="post.status !== 'published'"
                            class="scms-btn"
                            type="button"
                            @click="publish(post.id)"
                        >
                            Publish
                        </button>
                        <button
                            v-else
                            class="scms-btn"
                            type="button"
                            @click="unpublish(post.id)"
                        >
                            Unpublish
                        </button>
                        <button class="scms-btn" type="button" @click="destroy(post.id)">Delete</button>
                    </div>
                </div>
            </div>
            <p v-if="posts.length === 0 && editingId === null" class="scms-muted" style="margin: 0.5rem 0 0">
                No posts yet.
            </p>
        </div>
    </div>
</template>
