import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SiteCmsPageProps, SiteCmsShared } from '../types';

const empty: SiteCmsShared = {
    enabled: false,
    preview: false,
    embedded: false,
    status: 'published',
    pageKey: 'home',
    values: {},
    site: { logo: null, favicon: null, title: null, description: null, image: null },
    pages: [],
    draft: { dirty: false, count: 0 },
    routes: {
        login: '/cms/login',
        logout: '/cms/login/logout',
        forgotPassword: '/cms/login/forgot-password',
        resetPassword: '/cms/login/reset-password',
        dashboard: '/cms',
        draft: '/cms/draft',
        publish: '/cms/publish',
        resources: '/cms/resources',
        enter: '/cms/enter',
        exit: '/cms/exit',
    },
    upload: {
        accept: '.jpg,.jpeg,.png,.gif,.webp,.svg,.ico,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip,.rtf',
        imageAccept: 'image/*,.jpg,.jpeg,.png,.gif,.webp,.svg,.ico',
        maxKilobytes: 12288,
    },
};

export function useSiteCms() {
    const page = usePage<SiteCmsPageProps>();
    const siteCms = computed(() => page.props.siteCms ?? empty);

    return { siteCms };
}
