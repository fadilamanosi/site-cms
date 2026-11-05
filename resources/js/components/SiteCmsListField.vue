<script setup lang="ts">
import { computed, ref } from 'vue';
import { emptyListItem } from '../fieldValue';
import type { ListFieldColumn, ListItem } from '../types';
import SiteCmsImageField from './SiteCmsImageField.vue';

const props = defineProps<{
    schema: ListFieldColumn[];
}>();

const items = defineModel<ListItem[]>({ default: () => [] });
const open = ref<number | null>(null);

const columns = computed(() =>
    props.schema.map((column) => ({
        key: column.key,
        label: column.label || column.key,
        type: column.type === 'url' || column.type === 'image' ? column.type : 'text',
    })),
);

function toggle(index: number): void {
    open.value = open.value === index ? null : index;
}

function add(): void {
    items.value = [...items.value, emptyListItem(columns.value)];
    open.value = items.value.length - 1;
}

function duplicate(index: number): void {
    const current = items.value[index];
    if (!current) {
        return;
    }
    const copy = { ...current };
    items.value = [...items.value.slice(0, index + 1), copy, ...items.value.slice(index + 1)];
    open.value = index + 1;
}

function remove(index: number): void {
    items.value = items.value.filter((_, current) => current !== index);
    if (open.value === null) {
        return;
    }
    if (open.value === index) {
        open.value = null;
    } else if (open.value > index) {
        open.value -= 1;
    }
}

function move(index: number, direction: number): void {
    const next = index + direction;
    if (next < 0 || next >= items.value.length) {
        return;
    }
    const copy = [...items.value];
    const current = copy[index];
    const other = copy[next];
    if (!current || !other) {
        return;
    }
    copy[index] = other;
    copy[next] = current;
    items.value = copy;
    if (open.value === index) {
        open.value = next;
    } else if (open.value === next) {
        open.value = index;
    }
}

function patch(index: number, key: string, value: string): void {
    items.value = items.value.map((item, current) => (current === index ? { ...item, [key]: value } : item));
}

function itemLabel(item: ListItem, index: number): string {
    const primary = columns.value[0]?.key;
    const value = primary ? item[primary] : '';

    return value || `Item ${index + 1}`;
}
</script>

<template>
    <div class="scms-gallery">
        <div v-for="(item, index) in items" :key="index" class="scms-gallery-item" :class="{ 'is-open': open === index }">
            <button
                class="scms-gallery-h"
                type="button"
                :aria-expanded="open === index"
                @click="toggle(index)"
            >
                <span>{{ index + 1 }}. {{ itemLabel(item, index) }}</span>
                <svg
                    class="scms-chevron"
                    :class="{ 'scms-chevron--up': open === index }"
                    viewBox="0 0 12 12"
                    aria-hidden="true"
                >
                    <path d="M2.5 4.25 L6 7.75 L9.5 4.25" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <div v-if="open === index" class="scms-gallery-body">
                <div class="scms-gallery-tools">
                    <button type="button" class="scms-btn" :disabled="index === 0" @click="move(index, -1)">Up</button>
                    <button type="button" class="scms-btn" :disabled="index === items.length - 1" @click="move(index, 1)">
                        Down
                    </button>
                    <button type="button" class="scms-btn" @click="duplicate(index)">Duplicate</button>
                    <button type="button" class="scms-btn" @click="remove(index)">Remove</button>
                </div>
                <template v-for="column in columns" :key="column.key">
                    <SiteCmsImageField
                        v-if="column.type === 'image'"
                        :model-value="item[column.key] || ''"
                        :label="column.label"
                        @update:model-value="patch(index, column.key, $event)"
                    />
                    <label v-else class="scms-field">
                        <span>{{ column.label }}</span>
                        <input
                            :value="item[column.key] || ''"
                            :type="column.type === 'url' ? 'url' : 'text'"
                            @input="patch(index, column.key, ($event.target as HTMLInputElement).value)"
                        />
                    </label>
                </template>
            </div>
        </div>
        <button class="scms-btn" type="button" @click="add">Add item</button>
    </div>
</template>
