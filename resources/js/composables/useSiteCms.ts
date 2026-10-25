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
        login: '/staff/login',
        logout: '/staff/login/logout',
        forgotPassword: '/staff/login/forgot-password',
        resetPassword: '/staff/login/reset-password',
        dashboard: '/staff/site',
        draft: '/staff/site/draft',
        publish: '/staff/site/publish',
        resources: '/staff/site/resources',
        enter: '/staff/site/enter',
        exit: '/staff/site/exit',
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
