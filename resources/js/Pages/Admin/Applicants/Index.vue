<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
const props = defineProps<{ applicants: any; filters: any }>();
const filters = reactive({ ...props.filters });
const search = () => router.get('/admin/applicants', filters, { preserveState: true, replace: true });
const statuses: Record<string, { label: string; classes: string }> = {
  unpaid: { label: 'Belum Bayar', classes: 'bg-red-100 text-red-700 ring-red-200' },
  pending: { label: 'Menunggu', classes: 'bg-amber-100 text-amber-800 ring-amber-200' },
  paid: { label: 'Lunas', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
  failed: { label: 'Gagal', classes: 'bg-red-100 text-red-700 ring-red-200' },
  expired: { label: 'Kedaluwarsa', classes: 'bg-slate-200 text-slate-700 ring-slate-300' },
  refunded: { label: 'Dikembalikan', classes: 'bg-violet-100 text-violet-700 ring-violet-200' },
  pending_review: { label: 'Menunggu Review', classes: 'bg-amber-100 text-amber-800 ring-amber-200' },
  complete: { label: 'Berkas Lengkap', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
  incomplete: { label: 'Belum Lengkap', classes: 'bg-red-100 text-red-700 ring-red-200' },
  not_scheduled: { label: 'Belum Dijadwalkan', classes: 'bg-slate-100 text-slate-700 ring-slate-200' },
  scheduled: { label: 'Dijadwalkan', classes: 'bg-blue-100 text-blue-700 ring-blue-200' },
  passed: { label: 'Diterima', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
  not_passed: { label: 'Tidak Diterima', classes: 'bg-red-100 text-red-700 ring-red-200' },
};
const status = (value: string) => statuses[value] || { label: value?.replaceAll('_', ' ') || '-', classes: 'bg-slate-100 text-slate-700 ring-slate-200' };
</script>

<template>
  <Head title="Data Pendaftar" />
  <main class="min-h-screen bg-slate-100 p-5">
    <section class="mx-auto max-w-7xl">
      <Link href="/admin/dashboard" class="text-sm text-emerald-700">← Dashboard</Link>
      <h1 class="text-3xl font-bold text-emerald-950">Data pendaftar</h1>
      <form class="mt-6 grid gap-3 rounded-2xl bg-white p-4 md:grid-cols-4" @submit.prevent="search">
        <input v-model="filters.search" placeholder="Nama / nomor pendaftaran" class="rounded-xl border-slate-300" />
        <select v-model="filters.payment_status" class="rounded-xl border-slate-300"><option value="">Semua pembayaran</option><option value="unpaid">Belum bayar</option><option value="pending">Menunggu</option><option value="paid">Lunas</option><option value="failed">Gagal</option></select>
        <select v-model="filters.document_status" class="rounded-xl border-slate-300"><option value="">Semua berkas</option><option value="pending_review">Menunggu review</option><option value="complete">Lengkap</option><option value="incomplete">Belum lengkap</option></select>
        <button class="rounded-xl bg-emerald-800 py-3 font-bold text-white">Filter</button>
      </form>
      <div class="mt-5 overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[980px] text-left text-sm">
          <thead class="bg-emerald-950 text-white"><tr><th class="p-4">Nomor</th><th class="p-4">Nama</th><th class="p-4">Pembayaran</th><th class="p-4">Berkas</th><th class="p-4">Seleksi</th><th class="p-4 text-right">Aksi</th></tr></thead>
          <tbody>
            <tr v-for="a in applicants.data" :key="a.id" class="border-b transition hover:bg-emerald-50/40">
              <td class="p-4 font-bold text-emerald-800">{{ a.registration_number }}</td><td class="p-4 font-semibold text-slate-800">{{ a.full_name }}</td>
              <td class="p-4"><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset" :class="status(a.payment_status).classes">{{ status(a.payment_status).label }}</span></td>
              <td class="p-4"><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset" :class="status(a.document_status).classes">{{ status(a.document_status).label }}</span></td>
              <td class="p-4"><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset" :class="status(a.selection_status).classes">{{ status(a.selection_status).label }}</span></td>
              <td class="p-4 text-right"><Link :href="`/admin/applicants/${a.id}`" class="inline-flex items-center rounded-lg bg-emerald-800 px-4 py-2 font-bold text-white transition hover:bg-emerald-700">Detail →</Link></td>
            </tr>
            <tr v-if="!applicants.data.length"><td colspan="6" class="p-10 text-center text-slate-500">Belum ada data pendaftar.</td></tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</template>
