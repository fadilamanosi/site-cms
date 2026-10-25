<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useSiteCms } from '../../composables/useSiteCms';
import { applyHostColorScheme } from '../../hostTheme';
import '../../../css/site-cms.css';

const props = defineProps<{
    status?: string | null;
    retryUntil?: number | null;
}>();

const { siteCms } = useSiteCms();
const root = ref<HTMLElement | null>(null);
const remaining = ref(0);
let timer = 0;

onMounted(() => {
    applyHostColorScheme(root.value);
    tick();
    timer = window.setInterval(tick, 250);
});
onUnmounted(() => {
    window.clearInterval(timer);
});

watch(
    () => props.retryUntil,
    () => tick(),
);

const form = useForm({
    email: '',
});

const waiting = computed(() => remaining.value > 0);
const clock = computed(() => {
    const minutes = Math.floor(remaining.value / 60);
    const seconds = remaining.value % 60;

    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
});
const submitLabel = computed(() => (waiting.value ? `Send reset link (${clock.value})` : 'Send reset link'));

function tick(): void {
    const until = props.retryUntil ?? 0;
    remaining.value = until > 0 ? Math.max(0, until - Math.floor(Date.now() / 1000)) : 0;
}

function submit(): void {
    if (!siteCms.value.routes.forgotPassword || waiting.value) {
        return;
    }
    form.post(siteCms.value.routes.forgotPassword);
}
</script>

<template>
    <Head title="Forgot password" />
    <div ref="root" class="scms-login">
        <form class="scms-card" @submit.prevent="submit">
            <h1>Forgot password</h1>
            <p class="scms-muted">Enter the email for this Site CMS login. If it matches an account, we will send a reset link.</p>
            <p v-if="status" class="scms-status">{{ status }}</p>
            <p v-if="waiting" class="scms-status" aria-live="polite">Please wait {{ clock }} before retrying.</p>
            <label class="scms-field">
                <span>Email</span>
                <input v-model="form.email" type="email" required autocomplete="username" />
            </label>
            <p v-if="form.errors.email" class="scms-error">{{ form.errors.email }}</p>
            <button class="scms-btn scms-btn-primary" type="submit" :disabled="form.processing || waiting">
                {{ submitLabel }}
            </button>
            <p class="scms-login-nav">
                <Link class="scms-text-link" :href="siteCms.routes.login">Back to sign in</Link>
            </p>
        </form>
    </div>
</template>
