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
                parseHTML: (element) => element.getAttribute('width') || element.style.width || null,
                renderHTML: (attributes) => {
                    if (!attributes.width) {
                        return {};
                    }

                    return {
                        width: attributes.width,
                        style: `width: ${attributes.width}`,
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
