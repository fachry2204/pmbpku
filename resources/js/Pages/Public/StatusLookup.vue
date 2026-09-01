<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
const lookup = useForm({ identifier: '' });
</script>

<template>
  <Head title="Cek Status" />
  <main class="islamic-gradient-page grid min-h-screen place-items-center p-5">
    <section class="islamic-glass-card islamic-ornament-card w-full max-w-lg rounded-3xl p-8">
      <Link href="/" class="text-sm font-semibold text-emerald-700">← Kembali ke halaman utama</Link>
      <p class="mt-6 font-semibold text-emerald-700">PMB Pendidikan Kader Ulama</p>
      <h1 class="mt-1 text-3xl font-bold text-emerald-950">Cek status pendaftaran</h1>
      <p class="mt-3 text-sm leading-6 text-slate-600">Masukkan salah satu data yang digunakan saat mendaftar: email atau nomor HP/WhatsApp.</p>

      <form class="mt-7 space-y-4" @submit.prevent="lookup.post('/cek-status')">
        <label class="block">
          <span class="text-sm font-semibold">Email atau nomor HP terdaftar</span>
          <input v-model="lookup.identifier" type="text" required autofocus autocomplete="off" placeholder="contoh@email.com atau 081234567890" class="mt-2 w-full rounded-xl border-slate-300" />
          <small v-if="lookup.errors.identifier" class="mt-2 block text-sm text-red-700">{{ lookup.errors.identifier }}</small>
        </label>
        <button :disabled="lookup.processing" class="w-full rounded-xl bg-emerald-800 py-3 font-bold text-white disabled:opacity-50">
          {{ lookup.processing ? 'Mencari…' : 'Tampilkan Status Pendaftaran' }}
        </button>
      </form>
    </section>
  </main>
</template>
