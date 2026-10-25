<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import SiteCmsPasswordField from '../../components/SiteCmsPasswordField.vue';
import { useSiteCms } from '../../composables/useSiteCms';
import { applyHostColorScheme } from '../../hostTheme';
import '../../../css/site-cms.css';

const props = defineProps<{
    token: string;
    email: string;
}>();

const { siteCms } = useSiteCms();
const root = ref<HTMLElement | null>(null);

onMounted(() => {
    applyHostColorScheme(root.value);
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit(): void {
    if (!siteCms.value.routes.resetPassword) {
        return;
    }
    form.post(siteCms.value.routes.resetPassword);
}
</script>

<template>
    <Head title="Reset password" />
    <div ref="root" class="scms-login">
        <form class="scms-card" @submit.prevent="submit">
            <h1>Reset password</h1>
            <label class="scms-field">
                <span>Email</span>
                <input v-model="form.email" type="email" required autocomplete="username" />
            </label>
            <SiteCmsPasswordField v-model="form.password" label="New password" autocomplete="new-password" />
            <SiteCmsPasswordField
                v-model="form.password_confirmation"
                label="Confirm password"
                autocomplete="new-password"
            />
            <p v-if="form.errors.email" class="scms-error">{{ form.errors.email }}</p>
            <p v-else-if="form.errors.password" class="scms-error">{{ form.errors.password }}</p>
            <p v-else-if="form.errors.token" class="scms-error">{{ form.errors.token }}</p>
            <button class="scms-btn scms-btn-primary" type="submit" :disabled="form.processing">Set new password</button>
            <p class="scms-login-nav">
                <Link class="scms-text-link" :href="siteCms.routes.login">Back to sign in</Link>
            </p>
        </form>
    </div>
</template>
