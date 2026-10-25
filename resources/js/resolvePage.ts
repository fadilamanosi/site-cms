import { h, type DefineComponent } from 'vue';
import SiteCmsEditorLayout from './components/SiteCmsEditorLayout.vue';
import type { SiteCmsPageProps } from './types';

type PageModule = DefineComponent | { default: DefineComponent };

function unwrap(mod: PageModule): DefineComponent {
    return ((mod as { default?: DefineComponent }).default ?? mod) as DefineComponent;
}

function wrapLayout(component: DefineComponent): void {
    const flag = '__siteCmsLayout' as const;
    const tagged = component as DefineComponent & { __siteCmsLayout?: boolean; layout?: unknown };
    if (tagged[flag]) {
        return;
    }
    tagged[flag] = true;
    const original = tagged.layout;

    tagged.layout = (hFn: typeof h, child: { props?: SiteCmsPageProps }) => {
        const enabled = Boolean(child.props?.siteCms?.enabled);
        const withSite =
            typeof original === 'function'
                ? (original as (h: typeof h, child: unknown) => unknown)(hFn, child)
                : original
                  ? hFn(original as DefineComponent, child.props ?? {}, () => child)
                  : child;
        if (!enabled) {
            return withSite;
        }
        return hFn(SiteCmsEditorLayout, () => withSite);
    };
}

export function resolveSiteCmsPage(
    name: string,
    hostPages: Record<string, PageModule>,
    cmsPages: Record<string, PageModule> = {},
): PageModule {
    const host = hostPages[`./Pages/${name}.vue`];
    if (host) {
        wrapLayout(unwrap(host));
        return host;
    }

    const match = Object.entries(cmsPages).find(([path]) => {
        return path.endsWith(`/Pages/${name}.vue`) || path.endsWith(`Pages/${name}.vue`);
    });
    if (!match) {
        throw new Error(`Inertia page not found: ${name}`);
    }
    wrapLayout(unwrap(match[1]));
    return match[1];
}
