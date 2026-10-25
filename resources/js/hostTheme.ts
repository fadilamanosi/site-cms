function parseRgb(value: string): [number, number, number] | null {
    const hex = value.trim().match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
    if (hex) {
        const raw = hex[1];
        const full = raw.length === 3 ? raw.split('').map((part) => part + part).join('') : raw;
        const n = Number.parseInt(full, 16);

        return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
    }
    const rgb = value.match(/rgba?\(\s*([\d.]+)\s*[, ]\s*([\d.]+)\s*[, ]\s*([\d.]+)/i);
    if (!rgb) {
        return null;
    }

    return [Number(rgb[1]), Number(rgb[2]), Number(rgb[3])];
}

function isDarkColor(value: string): boolean {
    const rgb = parseRgb(value);
    if (!rgb) {
        return false;
    }
    const [r, g, b] = rgb;
    const luminance = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;

    return luminance < 0.45;
}

export function applyHostColorScheme(el: HTMLElement | null): void {
    if (!el) {
        return;
    }
    const declared = getComputedStyle(document.documentElement).colorScheme.trim();
    if (declared && declared !== 'normal') {
        el.style.colorScheme = declared;
        return;
    }
    const bg =
        getComputedStyle(document.documentElement).getPropertyValue('--bg').trim()
        || getComputedStyle(document.body).backgroundColor;
    el.style.colorScheme = isDarkColor(bg) ? 'dark' : 'light';
}
