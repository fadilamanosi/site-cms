import type { GalleryItem, SiteCmsKind } from './types';

export type ButtonValue = {
    label: string;
    href: string;
    target?: string;
    download?: boolean | string;
};

export type ImageValue = {
    src: string;
    alt: string;
};

export type GalleryValue = {
    items: GalleryItem[];
};

export type LinkType = 'url' | 'email' | 'phone' | 'download';

export function unwrapEntry(entry: unknown): unknown {
    if (!entry || typeof entry !== 'object' || !('value' in entry)) {
        return undefined;
    }

    const inner = (entry as { value: unknown }).value;
    if (inner && typeof inner === 'object' && !Array.isArray(inner) && 'value' in inner) {
        const keys = Object.keys(inner);
        if (keys.length === 1) {
            return (inner as { value: unknown }).value;
        }
    }

    return inner;
}

export function asText(raw: unknown, fallback = ''): string {
    if (typeof raw === 'string') {
        return raw;
    }
    if (raw && typeof raw === 'object' && 'value' in raw) {
        const inner = (raw as { value: unknown }).value;
        if (typeof inner === 'string') {
            return inner;
        }
    }

    return fallback;
}

export function asImage(raw: unknown, fallbackSrc = '', fallbackAlt = ''): ImageValue {
    if (typeof raw === 'string') {
        return { src: raw, alt: fallbackAlt };
    }
    if (raw && typeof raw === 'object') {
        const record = raw as { src?: unknown; value?: unknown; alt?: unknown };

        return {
            src: String(record.src ?? record.value ?? fallbackSrc),
            alt: String(record.alt ?? fallbackAlt),
        };
    }

    return { src: fallbackSrc, alt: fallbackAlt };
}

export function asButton(raw: unknown, fallback: ButtonValue): ButtonValue {
    if (raw && typeof raw === 'object' && ('label' in raw || 'href' in raw)) {
        const record = raw as { label?: unknown; href?: unknown; target?: unknown; download?: unknown };
        const download = record.download === true || (typeof record.download === 'string' && record.download !== '')
            ? record.download
            : fallback.download;

        return {
            label: String(record.label ?? fallback.label),
            href: String(record.href ?? fallback.href),
            target: typeof record.target === 'string' ? record.target : fallback.target,
            download,
        };
    }

    return { ...fallback };
}

export function asGallery(raw: unknown, fallback: GalleryItem[] = []): GalleryValue {
    const source = Array.isArray(raw)
        ? raw
        : raw && typeof raw === 'object' && Array.isArray((raw as { items?: unknown }).items)
          ? ((raw as { items: unknown[] }).items)
          : fallback;

    return {
        items: source.map((item) => {
            if (!item || typeof item !== 'object') {
                return { src: String(item ?? ''), alt: '', caption: '' };
            }
            const record = item as { src?: unknown; value?: unknown; alt?: unknown; caption?: unknown; label?: unknown };

            return {
                src: String(record.src ?? record.value ?? ''),
                alt: String(record.alt ?? ''),
                caption: String(record.caption ?? record.label ?? ''),
            };
        }),
    };
}

export function linkTypeFromHref(href: string, download?: boolean | string): LinkType {
    if (download === true || (typeof download === 'string' && download !== '')) {
        return 'download';
    }
    const value = href.trim().toLowerCase();
    if (value.startsWith('mailto:') || (value.includes('@') && !value.includes('://') && !value.startsWith('tel:'))) {
        return 'email';
    }
    if (value.startsWith('tel:')) {
        return 'phone';
    }

    return 'url';
}

export function hrefDisplay(href: string, type: LinkType): string {
    if (type === 'email') {
        return href.replace(/^mailto:/i, '');
    }
    if (type === 'phone') {
        return href.replace(/^tel:/i, '');
    }

    return href;
}

function hrefPath(href: string): string {
    const value = href.trim().split('?')[0]?.split('#')[0] ?? href.trim();
    try {
        if (/^https?:\/\//i.test(value)) {
            return new URL(value).pathname;
        }
    } catch {
        // use the raw path
    }

    return value;
}

export function isMediaHref(href: string): boolean {
    return /^\/site-cms\/media\/\d+(?:\/|$)/.test(hrefPath(href));
}

export function isRemoteHref(href: string): boolean {
    return /^https?:\/\//i.test(href.trim()) && !isMediaHref(href);
}

export function fileNameFromHref(href: string): string {
    const path = hrefPath(href);
    const media = path.match(/^\/site-cms\/media\/\d+(?:\/(.+))?$/);
    if (media) {
        return media[1] ? decodeURIComponent(media[1]) : '';
    }
    const name = path.split('/').filter(Boolean).pop();

    return name ? decodeURIComponent(name) : href;
}

export function hrefStored(input: string, type: LinkType): string {
    const value = input.trim();
    if (type === 'email') {
        return value.toLowerCase().startsWith('mailto:') ? value : `mailto:${value}`;
    }
    if (type === 'phone') {
        return value.toLowerCase().startsWith('tel:') ? value : `tel:${value}`;
    }

    return value;
}

export function prettyLabel(fieldKey: string, label?: string): string {
    if (label && label !== fieldKey) {
        return label;
    }

    return fieldKey.split('.').slice(-2).join(' · ');
}

export function storedValue(kind: SiteCmsKind, value: unknown): unknown {
    if (kind === 'button' || kind === 'link' || kind === 'gallery') {
        return value;
    }
    if (kind === 'image' && value && typeof value === 'object') {
        return value;
    }
    if (value && typeof value === 'object' && 'value' in value) {
        return value;
    }

    return { value };
}
