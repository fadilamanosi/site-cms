import type { DefineComponent } from 'vue';

type PageModule = DefineComponent | { default: DefineComponent };

function unwrap(mod: PageModule): DefineComponent {
    return ((mod as { default?: DefineComponent }).default ?? mod) as DefineComponent;
}

export function resolveSiteCmsPage(
    name: string,
    hostPages: Record<string, PageModule>,
    cmsPages: Record<string, PageModule> = {},
): PageModule {
    const host = hostPages[`./Pages/${name}.vue`];
    if (host) {
        return host;
    }

    const match = Object.entries(cmsPages).find(([path]) => {
        return path.endsWith(`/Pages/${name}.vue`) || path.endsWith(`Pages/${name}.vue`);
    });
    if (!match) {
        throw new Error(`Inertia page not found: ${name}`);
    }

    return unwrap(match[1]);
}
