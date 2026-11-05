<script setup lang="ts">
import CharacterCount from '@tiptap/extension-character-count';
import Highlight from '@tiptap/extension-highlight';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import { Table } from '@tiptap/extension-table';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TableRow from '@tiptap/extension-table-row';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useSiteCmsUpload } from '../composables/useSiteCmsUpload';
import { ResizableImage } from '../extensions/resizableImage';
import { mediaUi } from '../mediaUi';

const model = defineModel<string>({ default: '' });

const props = withDefaults(defineProps<{
    placeholder?: string;
}>(), {
    placeholder: 'Write your post…',
});

const toolbarTick = ref(0);
const { upload } = useSiteCmsUpload();
let syncingFromParent = false;

const editor = useEditor({
    extensions: [
        StarterKit.configure({
            heading: { levels: [1, 2, 3] },
            codeBlock: {
                HTMLAttributes: { class: 'scms-rte-codeblock' },
            },
        }),
        Underline,
        Highlight.configure({ multicolor: false }),
        Link.configure({
            openOnClick: false,
            autolink: true,
            linkOnPaste: true,
            defaultProtocol: 'https',
            HTMLAttributes: {
                rel: 'noopener noreferrer',
                target: '_blank',
            },
        }),
        ResizableImage.configure({
            allowBase64: false,
            HTMLAttributes: {
                class: 'scms-rte-image',
            },
        }),
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        Table.configure({
            resizable: true,
            HTMLAttributes: { class: 'scms-rte-table' },
        }),
        TableRow,
        TableHeader,
        TableCell,
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
        CharacterCount,
    ],
    content: model.value || '',
    editorProps: {
        attributes: {
            class: 'scms-rte-surface ProseMirror',
        },
        handleDrop: (_view, event) => {
            const files = event.dataTransfer?.files;
            if (!files?.length) {
                return false;
            }
            const file = Array.from(files).find((entry) => entry.type.startsWith('image/'));
            if (!file) {
                return false;
            }
            event.preventDefault();
            void uploadDroppedImage(file);
            return true;
        },
        handlePaste: (_view, event) => {
            const files = event.clipboardData?.files;
            if (!files?.length) {
                return false;
            }
            const file = Array.from(files).find((entry) => entry.type.startsWith('image/'));
            if (!file) {
                return false;
            }
            event.preventDefault();
            void uploadDroppedImage(file);
            return true;
        },
    },
    onUpdate: ({ editor: current }) => {
        if (syncingFromParent) {
            return;
        }
        const html = current.getHTML();
        model.value = html === '<p></p>' ? '' : html;
    },
});

const words = computed(() => {
    void toolbarTick.value;
    return editor.value?.storage.characterCount?.words?.() ?? 0;
});

const characters = computed(() => {
    void toolbarTick.value;
    return editor.value?.storage.characterCount?.characters?.() ?? 0;
});

watch(model, (value) => {
    const current = editor.value;
    if (!current) {
        return;
    }
    const next = value || '';
    if (next === current.getHTML() || (next === '' && current.getHTML() === '<p></p>')) {
        return;
    }
    syncingFromParent = true;
    current.commands.setContent(next || '');
    queueMicrotask(() => {
        syncingFromParent = false;
    });
});

watch(
    editor,
    (instance, _prev, onCleanup) => {
        if (!instance) {
            return;
        }
        const bump = () => {
            toolbarTick.value += 1;
        };
        instance.on('selectionUpdate', bump);
        instance.on('transaction', bump);
        onCleanup(() => {
            instance.off('selectionUpdate', bump);
            instance.off('transaction', bump);
        });
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

async function uploadDroppedImage(file: File): Promise<void> {
    const url = await upload(file);
    if (!url || !editor.value) {
        return;
    }
    const alt = file.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ');
    editor.value.chain().focus().setImage({ src: url, alt }).run();
    toolbarTick.value += 1;
}

function isActive(name: string | Record<string, unknown>, attrs?: Record<string, unknown>): boolean {
    void toolbarTick.value;
    if (typeof name === 'string') {
        return editor.value?.isActive(name, attrs) ?? false;
    }

    return editor.value?.isActive(name) ?? false;
}

function canUndo(): boolean {
    void toolbarTick.value;
    return editor.value?.can().undo() ?? false;
}

function canRedo(): boolean {
    void toolbarTick.value;
    return editor.value?.can().redo() ?? false;
}

function run(command: () => void): void {
    command();
    toolbarTick.value += 1;
}

function setLink(): void {
    const current = editor.value;
    if (!current) {
        return;
    }
    if (current.isActive('link')) {
        current.chain().focus().unsetLink().run();
        toolbarTick.value += 1;
        return;
    }
    const existing = current.getAttributes('link').href;
    const href = window.prompt('Link URL', typeof existing === 'string' && existing ? existing : 'https://');
    if (!href) {
        return;
    }
    current.chain().focus().extendMarkRange('link').setLink({ href }).run();
    toolbarTick.value += 1;
}

function insertImage(): void {
    mediaUi.show('', (url, name) => {
        if (!url || !editor.value) {
            return;
        }
        const alt = name ? name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ') : '';
        editor.value.chain().focus().setImage({ src: url, alt }).run();
        toolbarTick.value += 1;
    }, 'image');
}

function insertTable(): void {
    editor.value?.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
    toolbarTick.value += 1;
}
</script>

<template>
    <div class="scms-rte">
        <div class="scms-rte-bar" role="toolbar" aria-label="Formatting">
            <div class="scms-rte-group">
                <button class="scms-btn" type="button" title="Undo" :disabled="!canUndo()" @click="run(() => editor?.chain().focus().undo().run())">Undo</button>
                <button class="scms-btn" type="button" title="Redo" :disabled="!canRedo()" @click="run(() => editor?.chain().focus().redo().run())">Redo</button>
            </div>
            <span class="scms-rte-sep" aria-hidden="true" />
            <div class="scms-rte-group">
                <button class="scms-btn" type="button" title="Bold" :class="{ 'is-on': isActive('bold') }" @click="run(() => editor?.chain().focus().toggleBold().run())"><strong>B</strong></button>
                <button class="scms-btn" type="button" title="Italic" :class="{ 'is-on': isActive('italic') }" @click="run(() => editor?.chain().focus().toggleItalic().run())"><em>I</em></button>
                <button class="scms-btn" type="button" title="Underline" :class="{ 'is-on': isActive('underline') }" @click="run(() => editor?.chain().focus().toggleUnderline().run())"><span style="text-decoration: underline">U</span></button>
                <button class="scms-btn" type="button" title="Strike" :class="{ 'is-on': isActive('strike') }" @click="run(() => editor?.chain().focus().toggleStrike().run())"><span style="text-decoration: line-through">S</span></button>
                <button class="scms-btn" type="button" title="Highlight" :class="{ 'is-on': isActive('highlight') }" @click="run(() => editor?.chain().focus().toggleHighlight().run())">Mark</button>
                <button class="scms-btn" type="button" title="Inline code" :class="{ 'is-on': isActive('code') }" @click="run(() => editor?.chain().focus().toggleCode().run())">Code</button>
            </div>
            <span class="scms-rte-sep" aria-hidden="true" />
            <div class="scms-rte-group">
                <button class="scms-btn" type="button" title="Heading 1" :class="{ 'is-on': isActive('heading', { level: 1 }) }" @click="run(() => editor?.chain().focus().toggleHeading({ level: 1 }).run())">H1</button>
                <button class="scms-btn" type="button" title="Heading 2" :class="{ 'is-on': isActive('heading', { level: 2 }) }" @click="run(() => editor?.chain().focus().toggleHeading({ level: 2 }).run())">H2</button>
                <button class="scms-btn" type="button" title="Heading 3" :class="{ 'is-on': isActive('heading', { level: 3 }) }" @click="run(() => editor?.chain().focus().toggleHeading({ level: 3 }).run())">H3</button>
                <button class="scms-btn" type="button" title="Paragraph" :class="{ 'is-on': isActive('paragraph') && !isActive('heading') }" @click="run(() => editor?.chain().focus().setParagraph().run())">P</button>
            </div>
            <span class="scms-rte-sep" aria-hidden="true" />
            <div class="scms-rte-group">
                <button class="scms-btn" type="button" title="Bullet list" :class="{ 'is-on': isActive('bulletList') }" @click="run(() => editor?.chain().focus().toggleBulletList().run())">• List</button>
                <button class="scms-btn" type="button" title="Numbered list" :class="{ 'is-on': isActive('orderedList') }" @click="run(() => editor?.chain().focus().toggleOrderedList().run())">1. List</button>
                <button class="scms-btn" type="button" title="Quote" :class="{ 'is-on': isActive('blockquote') }" @click="run(() => editor?.chain().focus().toggleBlockquote().run())">Quote</button>
                <button class="scms-btn" type="button" title="Code block" :class="{ 'is-on': isActive('codeBlock') }" @click="run(() => editor?.chain().focus().toggleCodeBlock().run())">{ }</button>
                <button class="scms-btn" type="button" title="Divider" @click="run(() => editor?.chain().focus().setHorizontalRule().run())">—</button>
            </div>
            <span class="scms-rte-sep" aria-hidden="true" />
            <div class="scms-rte-group">
                <button class="scms-btn" type="button" title="Align left" :class="{ 'is-on': isActive({ textAlign: 'left' }) }" @click="run(() => editor?.chain().focus().setTextAlign('left').run())">Left</button>
                <button class="scms-btn" type="button" title="Align center" :class="{ 'is-on': isActive({ textAlign: 'center' }) }" @click="run(() => editor?.chain().focus().setTextAlign('center').run())">Center</button>
                <button class="scms-btn" type="button" title="Align right" :class="{ 'is-on': isActive({ textAlign: 'right' }) }" @click="run(() => editor?.chain().focus().setTextAlign('right').run())">Right</button>
            </div>
            <span class="scms-rte-sep" aria-hidden="true" />
            <div class="scms-rte-group">
                <button class="scms-btn" type="button" title="Link" :class="{ 'is-on': isActive('link') }" @click="setLink">Link</button>
                <button class="scms-btn scms-btn-primary" type="button" title="Insert image from media library" @click="insertImage">Image</button>
                <button class="scms-btn" type="button" title="Insert table" :class="{ 'is-on': isActive('table') }" @click="insertTable">Table</button>
                <button
                    v-if="isActive('table')"
                    class="scms-btn"
                    type="button"
                    title="Add column"
                    @click="run(() => editor?.chain().focus().addColumnAfter().run())"
                >
                    +Col
                </button>
                <button
                    v-if="isActive('table')"
                    class="scms-btn"
                    type="button"
                    title="Add row"
                    @click="run(() => editor?.chain().focus().addRowAfter().run())"
                >
                    +Row
                </button>
                <button
                    v-if="isActive('table')"
                    class="scms-btn"
                    type="button"
                    title="Delete table"
                    @click="run(() => editor?.chain().focus().deleteTable().run())"
                >
                    Del table
                </button>
                <button class="scms-btn" type="button" title="Clear formatting" @click="run(() => editor?.chain().focus().unsetAllMarks().clearNodes().run())">Clear</button>
            </div>
        </div>
        <EditorContent :editor="editor" class="scms-rte-editor" />
        <div class="scms-rte-foot">
            <span>{{ words }} words</span>
            <span>{{ characters }} characters</span>
            <span class="scms-muted">Drag or paste images · click an image to resize</span>
        </div>
    </div>
</template>
