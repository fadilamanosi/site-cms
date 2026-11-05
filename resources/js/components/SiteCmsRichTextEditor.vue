<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const model = defineModel<string>({ default: '' });

const props = withDefaults(defineProps<{
    placeholder?: string;
}>(), {
    placeholder: 'Write your post…',
});

const surface = ref<HTMLElement | null>(null);
const focused = ref(false);

function syncFromModel(): void {
    const el = surface.value;
    if (!el || focused.value) {
        return;
    }
    const next = model.value || '';
    if (el.innerHTML !== next) {
        el.innerHTML = next;
    }
}

function emitHtml(): void {
    const el = surface.value;
    if (!el) {
        return;
    }
    model.value = el.innerHTML === '<br>' ? '' : el.innerHTML;
}

function command(cmd: string, value?: string): void {
    surface.value?.focus();
    document.execCommand(cmd, false, value);
    emitHtml();
}

function link(): void {
    const href = window.prompt('Link URL', 'https://');
    if (!href) {
        return;
    }
    command('createLink', href);
}

function heading(level: 'H2' | 'H3' | 'P'): void {
    command('formatBlock', level);
}

onMounted(() => {
    syncFromModel();
});

onBeforeUnmount(() => {
    emitHtml();
});

watch(model, () => {
    syncFromModel();
});
</script>

<template>
    <div class="scms-rte">
        <div class="scms-rte-bar" role="toolbar" aria-label="Formatting">
            <button class="scms-btn" type="button" title="Bold" @click="command('bold')">
                <strong>B</strong>
            </button>
            <button class="scms-btn" type="button" title="Italic" @click="command('italic')">
                <em>I</em>
            </button>
            <button class="scms-btn" type="button" title="Underline" @click="command('underline')">
                <span style="text-decoration: underline">U</span>
            </button>
            <button class="scms-btn" type="button" title="Heading" @click="heading('H2')">H2</button>
            <button class="scms-btn" type="button" title="Subheading" @click="heading('H3')">H3</button>
            <button class="scms-btn" type="button" title="Paragraph" @click="heading('P')">P</button>
            <button class="scms-btn" type="button" title="Bullet list" @click="command('insertUnorderedList')">• List</button>
            <button class="scms-btn" type="button" title="Numbered list" @click="command('insertOrderedList')">1. List</button>
            <button class="scms-btn" type="button" title="Quote" @click="command('formatBlock', 'BLOCKQUOTE')">Quote</button>
            <button class="scms-btn" type="button" title="Link" @click="link">Link</button>
            <button class="scms-btn" type="button" title="Clear formatting" @click="command('removeFormat')">Clear</button>
        </div>
        <div
            ref="surface"
            class="scms-rte-surface"
            contenteditable="true"
            role="textbox"
            aria-multiline="true"
            :data-placeholder="props.placeholder"
            @input="emitHtml"
            @focus="focused = true"
            @blur="focused = false; emitHtml()"
        />
    </div>
</template>
