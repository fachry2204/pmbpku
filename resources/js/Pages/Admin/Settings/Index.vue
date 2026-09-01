<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';

type Field = { key: string; label: string; type: string; hint?: string };
type Section = { title: string; description: string; accent: string; icon: string; fields: Field[] };

const props = defineProps<{ values: Record<string, any> }>();
const form = useForm(Object.fromEntries(Object.entries(props.values).map(([key, value]) => [key.replaceAll('.', '_'), value])) as Record<string, any>);

const sections: Section[] = [
  { title: 'Pendaftaran', description: 'Atur biaya utama yang berlaku untuk setiap calon mahasiswa.', accent: 'bg-amber-50 text-amber-700', icon: 'Rp', fields: [
    { key: 'pmb_registration_fee', label: 'Harga Pendaftaran (Rp)', type: 'number', hint: 'Nominal biaya pendaftaran sebelum biaya layanan.' },
  ]},
  { title: 'Payment Gateway', description: 'Konfigurasi lingkungan dan kredensial pembayaran otomatis.', accent: 'bg-blue-50 text-blue-700', icon: 'PG', fields: [
    { key: 'duitku_mode', label: 'Mode Gateway', type: 'select' },
    { key: 'duitku_merchant_code', label: 'Merchant Code', type: 'password' },
    { key: 'duitku_api_key', label: 'API Key', type: 'password' },
  ]},
  { title: 'WhatsApp / Fonnte', description: 'Hubungkan layanan pengiriman notifikasi WhatsApp.', accent: 'bg-emerald-50 text-emerald-700', icon: 'WA', fields: [
    { key: 'fonnte_base_url', label: 'Base URL Fonnte', type: 'url' },
    { key: 'fonnte_token', label: 'Token Fonnte', type: 'password' },
  ]},
  { title: 'Email / SMTP', description: 'Atur server email untuk pemberitahuan sistem.', accent: 'bg-violet-50 text-violet-700', icon: '@', fields: [
    { key: 'mail_host', label: 'SMTP Host', type: 'text' },
    { key: 'mail_port', label: 'SMTP Port', type: 'number' },
    { key: 'mail_username', label: 'SMTP Username', type: 'password' },
    { key: 'mail_password', label: 'SMTP App Password', type: 'password' },
    { key: 'mail_from_address', label: 'From Address', type: 'email' },
  ]},
];
</script>

<template>
  <Head title="Pengaturan Integrasi" />
  <main class="min-h-screen bg-slate-100 p-5 md:p-8">
    <section class="mx-auto max-w-5xl">
      <Link href="/admin/dashboard" class="text-sm font-bold text-emerald-700">← Dashboard</Link>
      <h1 class="mt-3 text-3xl font-extrabold text-emerald-950">Pengaturan Integrasi</h1>
      <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Setiap integrasi dipisahkan berdasarkan fungsinya. Nilai rahasia terenkripsi dan tidak pernah dikirim kembali ke browser; biarkan nilai bertanda titik untuk mempertahankan secret lama.</p>

      <form class="mt-7 space-y-6" @submit.prevent="form.put('/admin/settings')">
        <section v-for="section in sections" :key="section.title" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
          <header class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-sm font-black" :class="section.accent">{{ section.icon }}</span>
            <div><h2 class="text-lg font-extrabold text-slate-900">{{ section.title }}</h2><p class="mt-1 text-sm text-slate-500">{{ section.description }}</p></div>
          </header>
          <div class="grid gap-5 p-6 md:grid-cols-2">
            <label v-for="field in section.fields" :key="field.key" class="block" :class="section.fields.length === 1 ? 'md:max-w-md' : ''">
              <span class="text-sm font-bold text-slate-700">{{ field.label }}</span>
              <select v-if="field.type === 'select'" v-model="form[field.key]" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-4 py-3 focus:border-emerald-600 focus:ring-emerald-600"><option value="sandbox">Sandbox</option><option value="production">Production</option></select>
              <input v-else v-model="form[field.key]" :type="field.type" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-4 py-3 focus:border-emerald-600 focus:ring-emerald-600" autocomplete="off" />
              <small v-if="field.hint" class="mt-2 block text-xs leading-5 text-slate-400">{{ field.hint }}</small>
              <small v-if="form.errors[field.key]" class="mt-2 block text-xs font-semibold text-red-700">{{ form.errors[field.key] }}</small>
            </label>
          </div>
        </section>

        <div class="sticky bottom-4 flex justify-end rounded-2xl border border-emerald-900/10 bg-white/90 p-4 shadow-xl backdrop-blur">
          <button :disabled="form.processing" class="rounded-xl bg-emerald-800 px-7 py-3 font-bold text-white shadow-lg shadow-emerald-900/15 transition hover:bg-emerald-700 disabled:opacity-50">{{ form.processing ? 'Menyimpan…' : 'Simpan Semua Pengaturan' }}</button>
        </div>
      </form>
    </section>
  </main>
</template>
