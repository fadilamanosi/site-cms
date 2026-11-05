import Image from '@tiptap/extension-image';
import { VueNodeViewRenderer } from '@tiptap/vue-3';
import SiteCmsResizableImage from '../components/SiteCmsResizableImage.vue';

export const ResizableImage = Image.extend({
    name: 'image',
    addAttributes() {
        return {
            ...this.parent?.(),
            width: {
                default: null,
                parseHTML: (element) => {
                    const styleWidth = element.style.width;
                    if (styleWidth) {
                        return styleWidth;
                    }
                    const attr = element.getAttribute('width');
                    if (!attr) {
                        return null;
                    }
                    // Legacy px attrs become fluid percentages at render time via max-width.
                    return /^\d+$/.test(attr) ? `${attr}px` : attr;
                },
                renderHTML: (attributes) => {
                    const width = attributes.width as string | null;
                    if (!width) {
                        return { style: 'max-width: 100%; height: auto;' };
                    }

                    return {
                        style: `width: ${width}; max-width: 100%; height: auto;`,
                    };
                },
            },
            align: {
                default: 'center',
                parseHTML: (element) => element.getAttribute('data-align') || 'center',
                renderHTML: (attributes) => ({
                    'data-align': attributes.align || 'center',
                }),
            },
        };
    },
    addNodeView() {
        return VueNodeViewRenderer(SiteCmsResizableImage);
    },
});
