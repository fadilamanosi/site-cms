export type SiteCmsKind = 'text' | 'image' | 'color' | 'icon' | 'button' | 'gallery';

export type SiteCmsValue = {
    kind: SiteCmsKind;
    value: unknown;
};

export type SiteCmsPage = {
    key: string;
    path: string;
    component: string;
    label: string;
};

export type SiteCmsSite = {
    logo: string | null;
    favicon: string | null;
    title: string | null;
    description: string | null;
    image: string | null;
};

export type SiteCmsShared = {
    enabled: boolean;
    status: 'draft' | 'published';
    pageKey: string;
    values: Record<string, SiteCmsValue>;
    site: SiteCmsSite;
    pages: SiteCmsPage[];
    routes: {
        login: string;
        dashboard: string;
        draft: string;
        publish: string;
        resources: string;
        enter: string;
        exit: string;
    };
};

export type SiteCmsPageProps = {
    siteCms: SiteCmsShared;
};

export type ActiveField = {
    fieldKey: string;
    kind: SiteCmsKind;
    label: string;
    defaultValue: unknown;
};
