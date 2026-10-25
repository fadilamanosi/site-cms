<script setup lang="ts">
import { computed, ref } from 'vue';

withDefaults(
    defineProps<{
        modelValue: string;
        label: string;
        autocomplete?: string;
        required?: boolean;
    }>(),
    { autocomplete: 'current-password', required: true },
);

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const shown = ref(false);
const type = computed(() => (shown.value ? 'text' : 'password'));
const toggleLabel = computed(() => (shown.value ? 'Hide password' : 'Show password'));

function onInput(event: Event): void {
    emit('update:modelValue', (event.target as HTMLInputElement).value);
}
</script>

<template>
    <label class="scms-field">
        <span>{{ label }}</span>
        <div class="scms-password">
            <input
                :value="modelValue"
                :type="type"
                :required="required"
                :autocomplete="autocomplete"
                @input="onInput"
            />
            <button
                class="scms-password-toggle"
                type="button"
                :aria-label="toggleLabel"
                :aria-pressed="shown"
                @click="shown = !shown"
            >
                <svg v-if="shown" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M3 3l18 18M10.6 10.6A2 2 0 0 0 13.4 13.4M9.9 5.1A10.9 10.9 0 0 1 12 5c7 0 10 7 10 7a18.5 18.5 0 0 1-3.2 3.9M6.1 6.1A18.2 18.2 0 0 0 2 12s3 7 10 7a10.4 10.4 0 0 0 4.4-.9"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                    <path
                        d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <circle
                        cx="12"
                        cy="12"
                        r="3"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    />
                </svg>
            </button>
        </div>
    </label>
</template>
