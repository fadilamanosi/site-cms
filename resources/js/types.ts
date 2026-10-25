export type SiteCmsKind = 'text' | 'image' | 'color' | 'icon' | 'button' | 'link' | 'gallery';

export type GalleryItem = {
    src: string;
    alt: string;
    caption: string;
};

export type SiteCmsValue = {
    kind: SiteCmsKind;
    value: unknown;
};

export type SiteCmsPage = {
    key: string;
    path: string;
    component: string;
    label: string;
    preview_url?: string;
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
    preview: boolean;
    embedded: boolean;
    status: 'draft' | 'published';
    pageKey: string;
    values: Record<string, SiteCmsValue>;
    site: SiteCmsSite;
    pages: SiteCmsPage[];
    draft: {
        dirty: boolean;
        count: number;
    };
    routes: {
        login: string;
        logout: string | null;
        forgotPassword: string | null;
        resetPassword: string | null;
        dashboard: string;
        draft: string;
        publish: string;
        resources: string;
        enter: string;
        exit: string;
    };
    upload: {
        accept: string;
        imageAccept: string;
        maxKilobytes: number;
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
