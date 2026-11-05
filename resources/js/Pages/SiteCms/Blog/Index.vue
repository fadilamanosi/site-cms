<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import SiteCmsDeskNav from '../../../components/SiteCmsDeskNav.vue';
import SiteCmsMediaModal from '../../../components/SiteCmsMediaModal.vue';
import { useSiteCms } from '../../../composables/useSiteCms';
import { applyHostColorScheme } from '../../../hostTheme';
import { mediaUi } from '../../../mediaUi';
import type { BlogPostCard } from '../../../types';
import '../../../../css/site-cms.css';

type DeskPager = {
    data: BlogPostCard[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    status: 'draft' | 'published' | null;
    q: string;
};

const props = defineProps<{
    pager: DeskPager;
}>();

const { siteCms } = useSiteCms();
const root = ref<HTMLElement | null>(null);
const search = ref(props.pager.q ?? '');

const editUrl = (id: number): string => {
    const template = siteCms.value.routes.blogEdit;
    return template ? template.replace('{id}', String(id)) : '#';
};

const destroyUrl = (id: number): string => {
    const template = siteCms.value.routes.blogDestroy;
    return template ? template.replace('{id}', String(id)) : '#';
};

const publishUrl = (id: number): string => {
    const template = siteCms.value.routes.blogPublish;
    return template ? template.replace('{id}', String(id)) : '#';
};

const unpublishUrl = (id: number): string => {
    const template = siteCms.value.routes.blogUnpublish;
    return template ? template.replace('{id}', String(id)) : '#';
};

const statusFilter = computed(() => props.pager.status);

function visit(query: Record<string, string | number | undefined>): void {
    const href = siteCms.value.routes.blogIndex;
    if (!href) {
        return;
    }
    const next: Record<string, string | number> = {};
    const status = query.status ?? statusFilter.value ?? undefined;
    const q = query.q !== undefined ? query.q : search.value;
    const page = query.page;
    if (status) {
        next.status = String(status);
    }
    if (q) {
        next.q = String(q);
    }
    if (page && Number(page) > 1) {
        next.page = Number(page);
    }
    router.get(href, next, { preserveState: true, preserveScroll: true });
}

function setStatus(status: '' | 'draft' | 'published'): void {
    visit({ status: status || undefined, page: 1 });
}

function submitSearch(): void {
    visit({ q: search.value, page: 1 });
}

function go(page: number): void {
    visit({ page });
}

function publish(id: number): void {
    router.post(publishUrl(id), {}, { preserveScroll: true });
}

function unpublish(id: number): void {
    router.post(unpublishUrl(id), {}, { preserveScroll: true });
}

function destroy(id: number): void {
    if (!window.confirm('Delete this post permanently?')) {
        return;
    }
    router.delete(destroyUrl(id), { preserveScroll: true });
}

function exit(): void {
    router.post(siteCms.value.routes.exit);
}

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }
    try {
        return new Date(value).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return '—';
    }
}

onMounted(() => {
    applyHostColorScheme(root.value);
});
</script>

<template>
    <Head title="Blog" />
    <div ref="root" class="scms-editor scms-blog-desk">
        <header class="scms-top">
            <div class="scms-top-lead">
                <SiteCmsDeskNav active="blog" />
                <h1>Posts</h1>
            </div>
            <div class="scms-actions">
                <Link
                    v-if="siteCms.routes.blogCreate"
                    class="scms-btn scms-btn-primary"
                    :href="siteCms.routes.blogCreate"
                >
                    Add new
                </Link>
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

        <div class="scms-blog-toolbar">
            <form class="scms-blog-search" @submit.prevent="submitSearch">
                <input v-model="search" type="search" placeholder="Search posts…" />
                <button class="scms-btn" type="submit">Search</button>
            </form>
            <div class="scms-blog-filters" role="group" aria-label="Status">
                <button
                    class="scms-btn"
                    type="button"
                    :class="{ 'scms-btn-primary': !statusFilter }"
                    @click="setStatus('')"
                >
                    All
                </button>
                <button
                    class="scms-btn"
                    type="button"
                    :class="{ 'scms-btn-primary': statusFilter === 'published' }"
                    @click="setStatus('published')"
                >
                    Published
                </button>
                <button
                    class="scms-btn"
                    type="button"
                    :class="{ 'scms-btn-primary': statusFilter === 'draft' }"
                    @click="setStatus('draft')"
                >
                    Drafts
                </button>
            </div>
        </div>

        <div class="scms-blog-list">
            <table class="scms-blog-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="post in pager.data" :key="post.id">
                        <td>
                            <Link class="scms-blog-title" :href="editUrl(post.id)">{{ post.title }}</Link>
                            <p v-if="post.excerpt" class="scms-muted scms-blog-excerpt">{{ post.excerpt }}</p>
                        </td>
                        <td>
                            <span
                                class="scms-blog-status"
                                :class="post.status === 'published' ? 'is-live' : 'is-draft'"
                            >
                                {{ post.status === 'published' ? 'Published' : 'Draft' }}
                            </span>
                        </td>
                        <td>{{ formatDate(post.updatedAt ?? post.publishedAt) }}</td>
                        <td>
                            <div class="scms-gallery-tools" style="margin: 0">
                                <Link class="scms-btn" :href="editUrl(post.id)">Edit</Link>
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
                        </td>
                    </tr>
                    <tr v-if="pager.data.length === 0">
                        <td colspan="4" class="scms-blog-empty">
                            No posts yet.
                            <Link v-if="siteCms.routes.blogCreate" :href="siteCms.routes.blogCreate">
                                Write the first one
                            </Link>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav class="scms-pager" aria-label="Blog pages">
            <button
                class="scms-btn"
                type="button"
                :disabled="pager.current_page <= 1"
                @click="go(pager.current_page - 1)"
            >
                Prev
            </button>
            <span>Page {{ pager.current_page }} of {{ pager.last_page }} · {{ pager.total }} posts</span>
            <button
                class="scms-btn"
                type="button"
                :disabled="pager.current_page >= pager.last_page"
                @click="go(pager.current_page + 1)"
            >
                Next
            </button>
        </nav>

        <SiteCmsMediaModal />
    </div>
</template>
