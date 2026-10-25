<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import SiteCmsPasswordField from '../../components/SiteCmsPasswordField.vue';
import { useSiteCms } from '../../composables/useSiteCms';
import { applyHostColorScheme } from '../../hostTheme';
import '../../../css/site-cms.css';

const { siteCms } = useSiteCms();
const root = ref<HTMLElement | null>(null);

onMounted(() => {
    applyHostColorScheme(root.value);
});

const form = useForm({
    email: '',
    password: '',
});

function submit(): void {
    form.post(siteCms.value.routes.login);
}
</script>

<template>
    <Head title="Sign in" />
    <div ref="root" class="scms-login">
        <form class="scms-card" @submit.prevent="submit">
            <h1>Site CMS</h1>
            <label class="scms-field">
                <span>Email</span>
                <input v-model="form.email" type="email" required autocomplete="username" />
            </label>
            <SiteCmsPasswordField v-model="form.password" label="Password" autocomplete="current-password" />
            <p v-if="siteCms.routes.forgotPassword" class="scms-login-nav">
                <Link class="scms-text-link" :href="siteCms.routes.forgotPassword">Forgot password?</Link>
            </p>
            <p v-if="form.errors.email" class="scms-error">{{ form.errors.email }}</p>
            <button class="scms-btn scms-btn-primary" type="submit" :disabled="form.processing">Sign in</button>
        </form>
    </div>
</template>
