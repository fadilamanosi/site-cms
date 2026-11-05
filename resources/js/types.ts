export type SiteCmsKind = 'text' | 'image' | 'color' | 'icon' | 'button' | 'link' | 'gallery' | 'list';

export type GalleryItem = {
    src: string;
    alt: string;
    caption: string;
};

export type ListFieldColumn = {
    key: string;
    label: string;
    type?: 'text' | 'url' | 'image';
};

export type ListItem = Record<string, string>;

export type ListValue = {
    schema: ListFieldColumn[];
    items: ListItem[];
};

export type BlogCover = {
    src: string;
    alt: string;
};

export type BlogPostCard = {
    id: number;
    slug: string;
    title: string;
    excerpt: string;
    href: string;
    cover: BlogCover;
    publishedAt: string | null;
    tags: string[];
    body?: string;
    status?: 'draft' | 'published';
    seo?: {
        title: string | null;
        description: string | null;
        image: string | null;
    };
    sortOrder?: number;
    updatedAt?: string | null;
};

export type BlogSectionFeed = {
    items: BlogPostCard[];
    total: number;
    seeMore: { href: string; label: string } | null;
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
    blog: {
        enabled: boolean;
        indexPath: string;
        seeMoreLabel: string;
        posts: BlogPostCard[];
    };
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
        blogStore: string | null;
        blogUpdate: string | null;
        blogPublish: string | null;
        blogUnpublish: string | null;
        blogDestroy: string | null;
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
