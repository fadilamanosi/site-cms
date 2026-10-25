import type { ActiveField, SiteCmsKind } from './types';

export const SITE_CMS_SOURCE = 'site-cms';

export type SiteCmsMessage =
    | { source: typeof SITE_CMS_SOURCE; type: 'select'; field: ActiveField }
    | { source: typeof SITE_CMS_SOURCE; type: 'active'; fieldKey: string | null }
    | { source: typeof SITE_CMS_SOURCE; type: 'saved'; field: ActiveField; value: unknown }
    | { source: typeof SITE_CMS_SOURCE; type: 'patch'; fieldKey: string; kind: SiteCmsKind; value: unknown }
    | { source: typeof SITE_CMS_SOURCE; type: 'reload' };

export function isSiteCmsMessage(data: unknown): data is SiteCmsMessage {
    if (!data || typeof data !== 'object') {
        return false;
    }
    const message = data as { source?: unknown; type?: unknown };

    return message.source === SITE_CMS_SOURCE && typeof message.type === 'string';
}

function cloneMessage<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
}

export function postToParent(message: SiteCmsMessage): void {
    if (window.parent === window) {
        return;
    }
    window.parent.postMessage(cloneMessage(message), window.location.origin);
}

export function postToFrame(frame: HTMLIFrameElement | null, message: SiteCmsMessage): void {
    frame?.contentWindow?.postMessage(cloneMessage(message), window.location.origin);
}

export function previewUrl(path: string): string {
    const pathname = !path || path === '/' ? '/' : path.startsWith('/') ? path : `/${path}`;
    if (pathname === '/') {
        return '/?site_cms_preview=1';
    }

    return `${pathname}${pathname.includes('?') ? '&' : '?'}site_cms_preview=1`;
}
