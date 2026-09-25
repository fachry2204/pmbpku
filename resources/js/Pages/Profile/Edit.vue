<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head title="Profil Saya" />

    <AdminLayout>
        <main class="islamic-gradient-page min-h-screen px-4 py-8 md:px-8">
            <section class="mx-auto max-w-5xl">
                <div class="mb-6"><p class="text-sm font-bold text-[#d4af37]">← Pengaturan Akun</p><h1 class="mt-1 text-3xl font-extrabold text-white">Profil Saya</h1><p class="mt-2 text-sm text-emerald-50/80">Kelola data akun dan keamanan password Anda.</p></div>
                <div class="grid gap-6 lg:grid-cols-2">
                    <section class="rounded-2xl bg-white p-6 shadow-xl shadow-emerald-950/10 md:p-8">
                        <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
                    </section>
                    <section class="rounded-2xl bg-white p-6 shadow-xl shadow-emerald-950/10 md:p-8">
                        <UpdatePasswordForm />
                    </section>
                </div>
            </section>
        </main>
    </AdminLayout>
</template>
