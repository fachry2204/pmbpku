<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
const props = defineProps<{ initialIdentifier?: string }>();
const lookup = useForm({ identifier: props.initialIdentifier || '' });
</script>

<template>
  <Head title="Cek Status" />
  <main class="islamic-gradient-page grid min-h-screen place-items-center p-5">
    <section class="islamic-glass-card islamic-ornament-card w-full max-w-lg rounded-3xl p-8">
      <Link href="/" class="text-sm font-semibold text-emerald-700">← Kembali ke halaman utama</Link>
      <p class="mt-6 font-semibold text-emerald-700">PMB Pendidikan Kader Ulama</p>
      <h1 class="mt-1 text-3xl font-bold text-emerald-950">Cek status pendaftaran</h1>
      <p class="mt-3 text-sm leading-6 text-slate-600">Masukkan nomor pendaftaran, email, atau nomor HP/WhatsApp yang digunakan saat mendaftar.</p>

      <form class="mt-7 space-y-4" @submit.prevent="lookup.post('/cek-status')">
        <label class="block">
          <span class="text-sm font-semibold">Nomor pendaftaran, email, atau nomor HP</span>
          <input v-model="lookup.identifier" type="text" required autofocus autocomplete="off" placeholder="PKU-2026-000001, email, atau nomor HP" class="mt-2 w-full rounded-xl border-slate-300" />
          <small v-if="lookup.errors.identifier" class="mt-2 block text-sm text-red-700">{{ lookup.errors.identifier }}</small>
        </label>
        <button :disabled="lookup.processing" class="w-full rounded-xl bg-emerald-800 py-3 font-bold text-white disabled:opacity-50">
          {{ lookup.processing ? 'Mencari…' : 'Tampilkan Status Pendaftaran' }}
        </button>
      </form>
    </section>
  </main>
</template>
