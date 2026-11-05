<script setup lang="ts">
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { onBeforeUnmount, ref, watch } from 'vue';
import { mediaUi } from '../mediaUi';

const model = defineModel<string>({ default: '' });

const props = withDefaults(defineProps<{
    placeholder?: string;
}>(), {
    placeholder: 'Write your post…',
});

const toolbarTick = ref(0);
let syncingFromParent = false;

const editor = useEditor({
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
        }),
        Underline,
        Link.configure({
            openOnClick: false,
            autolink: true,
            defaultProtocol: 'https',
            HTMLAttributes: {
                rel: 'noopener noreferrer',
                target: '_blank',
            },
        }),
        Image.configure({
            allowBase64: false,
            HTMLAttributes: {
                class: 'scms-rte-image',
            },
        }),
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
    ],
    content: model.value || '',
    editorProps: {
        attributes: {
            class: 'scms-rte-surface ProseMirror',
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
</script>

<template>
    <div class="scms-rte">
        <div class="scms-rte-bar" role="toolbar" aria-label="Formatting">
            <button
                class="scms-btn"
                type="button"
                title="Undo"
                :disabled="!canUndo()"
                @click="run(() => editor?.chain().focus().undo().run())"
            >
                Undo
            </button>
            <button
                class="scms-btn"
                type="button"
                title="Redo"
                :disabled="!canRedo()"
                @click="run(() => editor?.chain().focus().redo().run())"
            >
                Redo
            </button>
            <span class="scms-rte-sep" aria-hidden="true" />
            <button
                class="scms-btn"
                type="button"
                title="Bold"
                :class="{ 'is-on': isActive('bold') }"
                @click="run(() => editor?.chain().focus().toggleBold().run())"
            >
                <strong>B</strong>
            </button>
            <button
                class="scms-btn"
                type="button"
                title="Italic"
                :class="{ 'is-on': isActive('italic') }"
                @click="run(() => editor?.chain().focus().toggleItalic().run())"
            >
                <em>I</em>
            </button>
            <button
                class="scms-btn"
                type="button"
                title="Underline"
                :class="{ 'is-on': isActive('underline') }"
                @click="run(() => editor?.chain().focus().toggleUnderline().run())"
            >
                <span style="text-decoration: underline">U</span>
            </button>
            <span class="scms-rte-sep" aria-hidden="true" />
            <button
                class="scms-btn"
                type="button"
                title="Heading 2"
                :class="{ 'is-on': isActive('heading', { level: 2 }) }"
                @click="run(() => editor?.chain().focus().toggleHeading({ level: 2 }).run())"
            >
                H2
            </button>
            <button
                class="scms-btn"
                type="button"
                title="Heading 3"
                :class="{ 'is-on': isActive('heading', { level: 3 }) }"
                @click="run(() => editor?.chain().focus().toggleHeading({ level: 3 }).run())"
            >
                H3
            </button>
            <button
                class="scms-btn"
                type="button"
                title="Paragraph"
                :class="{ 'is-on': isActive('paragraph') && !isActive('heading') }"
                @click="run(() => editor?.chain().focus().setParagraph().run())"
            >
                P
            </button>
            <span class="scms-rte-sep" aria-hidden="true" />
            <button
                class="scms-btn"
                type="button"
                title="Bullet list"
                :class="{ 'is-on': isActive('bulletList') }"
                @click="run(() => editor?.chain().focus().toggleBulletList().run())"
            >
                • List
            </button>
            <button
                class="scms-btn"
                type="button"
                title="Numbered list"
                :class="{ 'is-on': isActive('orderedList') }"
                @click="run(() => editor?.chain().focus().toggleOrderedList().run())"
            >
                1. List
            </button>
            <button
                class="scms-btn"
                type="button"
                title="Quote"
                :class="{ 'is-on': isActive('blockquote') }"
                @click="run(() => editor?.chain().focus().toggleBlockquote().run())"
            >
                Quote
            </button>
            <span class="scms-rte-sep" aria-hidden="true" />
            <button
                class="scms-btn"
                type="button"
                title="Align left"
                :class="{ 'is-on': isActive({ textAlign: 'left' }) }"
                @click="run(() => editor?.chain().focus().setTextAlign('left').run())"
            >
                Left
            </button>
            <button
                class="scms-btn"
                type="button"
                title="Align center"
                :class="{ 'is-on': isActive({ textAlign: 'center' }) }"
                @click="run(() => editor?.chain().focus().setTextAlign('center').run())"
            >
                Center
            </button>
            <button
                class="scms-btn"
                type="button"
                title="Align right"
                :class="{ 'is-on': isActive({ textAlign: 'right' }) }"
                @click="run(() => editor?.chain().focus().setTextAlign('right').run())"
            >
                Right
            </button>
            <span class="scms-rte-sep" aria-hidden="true" />
            <button
                class="scms-btn"
                type="button"
                title="Link"
                :class="{ 'is-on': isActive('link') }"
                @click="setLink"
            >
                Link
            </button>
            <button class="scms-btn scms-btn-primary" type="button" title="Insert image" @click="insertImage">
                Image
            </button>
            <button
                class="scms-btn"
                type="button"
                title="Clear formatting"
                @click="run(() => editor?.chain().focus().unsetAllMarks().clearNodes().run())"
            >
                Clear
            </button>
        </div>
        <EditorContent :editor="editor" class="scms-rte-editor" />
    </div>
</template>
