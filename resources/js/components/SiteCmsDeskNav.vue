<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useSiteCms } from '../composables/useSiteCms';

const props = withDefaults(defineProps<{
    active?: 'customize' | 'blog';
}>(), {
    active: 'customize',
});

const { siteCms } = useSiteCms();
const page = usePage();

const section = computed<'customize' | 'blog'>(() => {
    if (props.active === 'blog') {
        return 'blog';
    }
    const url = page.url.split('?')[0] ?? '';
    if (url.includes('/blog')) {
        return 'blog';
    }

    return 'customize';
});
</script>

<template>
    <nav class="scms-desk-nav" aria-label="CMS">
        <Link
            class="scms-desk-nav__link"
            :class="{ 'is-active': section === 'customize' }"
            :href="siteCms.routes.dashboard"
        >
            Customize
        </Link>
        <Link
            v-if="siteCms.blog?.enabled && siteCms.routes.blogIndex"
            class="scms-desk-nav__link"
            :class="{ 'is-active': section === 'blog' }"
            :href="siteCms.routes.blogIndex"
        >
            Blog
        </Link>
    </nav>
</template>
