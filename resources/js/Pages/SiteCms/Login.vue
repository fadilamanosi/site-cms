<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
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
            <label class="scms-field">
                <span>Password</span>
                <input v-model="form.password" type="password" required autocomplete="current-password" />
            </label>
            <p v-if="form.errors.email" class="scms-error">{{ form.errors.email }}</p>
            <button class="scms-btn scms-btn-primary" type="submit" :disabled="form.processing">Sign in</button>
        </form>
    </div>
</template>
