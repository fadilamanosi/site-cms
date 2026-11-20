import { Node, mergeAttributes } from '@tiptap/core';
import { VueNodeViewRenderer } from '@tiptap/vue-3';
import SiteCmsImageGallery from '../components/SiteCmsImageGallery.vue';

export type GalleryImageItem = {
    src: string;
    alt: string;
    caption: string;
};

export type GalleryLayout = 'grid' | 'grid-two' | 'grid-four' | 'grid-six' | 'portrait';

export function galleryLayoutClass(layout: string): string {
    switch (layout) {
        case 'grid-two':
            return 'scms-gallery-block scms-gallery-block--two perspective-post-photos perspective-post-photos-two';
        case 'grid-four':
            return 'scms-gallery-block scms-gallery-block--four perspective-post-photos perspective-post-photos-four';
        case 'grid-six':
            return 'scms-gallery-block scms-gallery-block--six perspective-post-photos perspective-post-photos-six';
        case 'portrait':
            return 'scms-gallery-block scms-gallery-block--portrait';
        default:
            return 'scms-gallery-block scms-gallery-block--grid perspective-post-photos';
    }
}

function parseItems(element: HTMLElement): GalleryImageItem[] {
    return [...element.querySelectorAll(':scope > figure')].map((figure) => {
        const img = figure.querySelector('img');
        const caption = figure.querySelector('figcaption');

        return {
            src: img?.getAttribute('src') || '',
            alt: img?.getAttribute('alt') || '',
            caption: (caption?.textContent || '').trim(),
        };
    }).filter((item) => item.src !== '');
}

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        imageGallery: {
            insertImageGallery: (items?: GalleryImageItem[], layout?: GalleryLayout) => ReturnType;
        };
    }
}

export const ImageGallery = Node.create({
    name: 'imageGallery',
    group: 'block',
    atom: true,
    draggable: true,
    selectable: true,

    addAttributes() {
        return {
            layout: {
                default: 'grid' satisfies GalleryLayout,
                parseHTML: (element) => element.getAttribute('data-layout') || 'grid',
                renderHTML: (attributes) => ({
                    'data-layout': attributes.layout || 'grid',
                }),
            },
            items: {
                default: [] as GalleryImageItem[],
                parseHTML: (element) => parseItems(element),
                renderHTML: () => ({}),
            },
        };
    },

    parseHTML() {
        return [{ tag: 'div[data-scms-gallery]' }];
    },

    renderHTML({ node, HTMLAttributes }) {
        const layout = String(node.attrs.layout || 'grid');
        const items = (Array.isArray(node.attrs.items) ? node.attrs.items : []) as GalleryImageItem[];
        const className = galleryLayoutClass(layout);

        return [
            'div',
            mergeAttributes(HTMLAttributes, {
                'data-scms-gallery': '',
                'data-layout': layout,
                class: className,
            }),
            ...items.map((item) => {
                const figureAttrs = layout === 'portrait'
                    ? { class: 'perspective-post-portrait' }
                    : {};
                const children: unknown[] = [
                    'figure',
                    figureAttrs,
                    ['img', { src: item.src, alt: item.alt || '', class: 'scms-rte-image' }],
                ];
                if (item.caption) {
                    children.push(['figcaption', {}, item.caption]);
                }

                return children;
            }),
        ];
    },

    addCommands() {
        return {
            insertImageGallery:
                (items = [], layout = 'grid') =>
                ({ commands }) =>
                    commands.insertContent({
                        type: this.name,
                        attrs: { items, layout },
                    }),
        };
    },

    addNodeView() {
        return VueNodeViewRenderer(SiteCmsImageGallery);
    },
});
