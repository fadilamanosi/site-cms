<script setup lang="ts">
import { mediaUi } from '../mediaUi';

const url = defineModel<string>({ default: '' });
const alt = defineModel<string>('alt', { default: '' });
withDefaults(
    defineProps<{
        label: string;
        withAlt?: boolean;
    }>(),
    { withAlt: false },
);

function pick(): void {
    mediaUi.show(
        url.value,
        (next, name) => {
            url.value = next;
            if (!alt.value && name) {
                alt.value = name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ');
            }
        },
        'image',
    );
}
</script>

<template>
    <div class="scms-field">
        <span>{{ label }}</span>
        <img v-if="url" class="scms-preview" :src="url" alt="" />
        <button class="scms-btn" type="button" @click="pick">Select media</button>
    </div>
    <label v-if="withAlt" class="scms-field">
        <span>Alt text</span>
        <input v-model="alt" type="text" />
    </label>
</template>
