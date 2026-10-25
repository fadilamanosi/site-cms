import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SiteCmsPageProps, SiteCmsShared } from '../types';

const empty: SiteCmsShared = {
    enabled: false,
    status: 'published',
    pageKey: 'home',
    values: {},
    site: { logo: null, favicon: null, title: null, description: null, image: null },
    pages: [],
    routes: {
        login: '/staff/login',
        dashboard: '/staff/site',
        draft: '/staff/site/draft',
        publish: '/staff/site/publish',
        resources: '/staff/site/resources',
        enter: '/staff/site/enter',
        exit: '/staff/site/exit',
    },
};

export function useSiteCms() {
    const page = usePage<SiteCmsPageProps>();
    const siteCms = computed(() => page.props.siteCms ?? empty);

    return { siteCms };
}
