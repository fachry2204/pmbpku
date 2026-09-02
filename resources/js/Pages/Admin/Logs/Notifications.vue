<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';

const props = defineProps<{ logs: any; filters: Record<string, string>; summary: { sent: number; queued: number; failed: number } }>();
const page = usePage() as any;
const filters = reactive({ ...props.filters });
const processing = ref(false);
const retrying = ref<number | null>(null);
const search = () => router.get('/admin/notification-logs', filters, { preserveState: true, replace: true });
const processPending = () => {
  processing.value = true;
  router.post('/admin/notification-logs/process-pending', {}, { preserveScroll: true, onFinish: () => processing.value = false });
};
const retry = (id: number) => {
  retrying.value = id;
  router.post(`/admin/notification-logs/${id}/retry`, {}, { preserveScroll: true, onFinish: () => retrying.value = null });
};
const statusMeta: Record<string, { label: string; classes: string; dot: string }> = {
  sent: { label: 'Berhasil Terkirim', classes: 'bg-emerald-50 text-emerald-700 ring-emerald-200', dot: 'bg-emerald-500' },
  queued: { label: 'Menunggu Diproses', classes: 'bg-amber-50 text-amber-700 ring-amber-200', dot: 'bg-amber-500' },
  failed: { label: 'Gagal Terkirim', classes: 'bg-red-50 text-red-700 ring-red-200', dot: 'bg-red-500' },
  skipped: { label: 'Tidak Diproses', classes: 'bg-slate-100 text-slate-700 ring-slate-200', dot: 'bg-slate-500' },
};
const meta = (status: string) => statusMeta[status] || statusMeta.queued;
const eventLabels: Record<string, string> = {
  registration_created: 'Pendaftaran dibuat', payment_unpaid: 'Belum bayar', payment_pending: 'Pembayaran diproses', payment_paid: 'Pembayaran berhasil', payment_failed: 'Pembayaran gagal', payment_expired: 'Pembayaran kedaluwarsa', payment_refunded: 'Pembayaran dikembalikan', document_pending_review: 'Berkas diperiksa', document_revision_submitted: 'Perbaikan berkas', document_incomplete: 'Berkas belum lengkap', document_complete: 'Berkas lengkap', selection_not_scheduled: 'Belum dijadwalkan', selection_scheduled: 'Jadwal seleksi', selection_attending_test: 'Mengikuti seleksi', selection_passed: 'Lulus seleksi', selection_not_passed: 'Tidak lulus seleksi', selection_withdrawn: 'Pendaftaran dibatalkan',
};
const dateTime = (value?: string) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—';
</script>

<template>
  <Head title="Pesan Terkirim" />
  <main class="min-h-screen p-5 md:p-8">
    <section class="mx-auto max-w-7xl">
      <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-xs font-bold uppercase tracking-[.2em] text-amber-300">Pusat Notifikasi</p><h1 class="mt-1 text-3xl font-extrabold text-white">Pesan Terkirim</h1><p class="mt-1 text-sm text-emerald-50/80">Pantau keberhasilan pengiriman email dan WhatsApp kepada pendaftar.</p></div>
        <button :disabled="processing || (!summary.queued && !summary.failed)" class="rounded-xl bg-amber-400 px-5 py-3 text-sm font-extrabold text-emerald-950 shadow-lg transition hover:bg-amber-300 disabled:cursor-not-allowed disabled:opacity-50" @click="processPending">{{ processing ? 'Memproses…' : `Proses Antrean (${summary.queued + summary.failed})` }}</button>
      </div>

      <div v-if="page.props.flash?.success" class="mt-5 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 font-bold text-emerald-800">{{ page.props.flash.success }}</div>
      <div v-if="page.props.flash?.error" class="mt-5 rounded-xl border border-red-300 bg-red-50 px-4 py-3 font-bold text-red-800">{{ page.props.flash.error }}</div>

      <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <article class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">Berhasil</p><p class="mt-2 text-3xl font-black text-emerald-700">{{ summary.sent }}</p><p class="mt-1 text-xs text-slate-500">Pesan sudah diterima provider.</p></article>
        <article class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">Antrean</p><p class="mt-2 text-3xl font-black text-amber-600">{{ summary.queued }}</p><p class="mt-1 text-xs text-slate-500">Menunggu untuk diproses.</p></article>
        <article class="rounded-2xl border border-red-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">Bermasalah</p><p class="mt-2 text-3xl font-black text-red-600">{{ summary.failed }}</p><p class="mt-1 text-xs text-slate-500">Perlu diperiksa atau dikirim ulang.</p></article>
      </div>

      <form class="mt-5 grid gap-3 rounded-2xl bg-white p-4 shadow-sm md:grid-cols-[minmax(0,1fr)_200px_200px_auto]" @submit.prevent="search">
        <input v-model="filters.search" placeholder="Nama atau nomor pendaftaran" class="rounded-xl border-slate-300" />
        <select v-model="filters.channel" class="rounded-xl border-slate-300"><option value="">Semua media</option><option value="email">Email</option><option value="whatsapp">WhatsApp</option></select>
        <select v-model="filters.status" class="rounded-xl border-slate-300"><option value="">Semua status</option><option value="sent">Berhasil terkirim</option><option value="queued">Menunggu diproses</option><option value="failed">Gagal terkirim</option><option value="skipped">Tidak diproses</option></select>
        <button class="rounded-xl bg-emerald-800 px-6 py-3 font-bold text-white">Filter</button>
      </form>

      <div class="mt-5 overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[1050px] text-left text-sm">
          <thead class="bg-emerald-950 text-white"><tr><th class="p-4">Pendaftar</th><th class="p-4">Pesan</th><th class="p-4">Media & penerima</th><th class="p-4">Status pengiriman</th><th class="p-4">Waktu</th><th class="p-4">Keterangan</th><th class="p-4 text-right">Aksi</th></tr></thead>
          <tbody>
            <tr v-for="log in logs.data" :key="log.id" class="border-b border-slate-100 align-top transition hover:bg-emerald-50/40">
              <td class="p-4"><b class="block text-slate-900">{{ log.applicant?.full_name || 'Data dihapus' }}</b><span class="mt-1 block text-xs font-semibold text-emerald-700">{{ log.applicant?.registration_number || '—' }}</span></td>
              <td class="p-4"><b class="text-slate-800">{{ eventLabels[log.event_type] || log.event_type }}</b><span v-if="log.event_type === 'selection_scheduled'" class="mt-1 block text-xs font-semibold text-blue-700">Berisi tanggal & jam seleksi</span></td>
              <td class="p-4"><span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-black uppercase text-slate-600">{{ log.channel }}</span><span class="ml-2 text-xs text-slate-600">{{ log.recipient_masked }}</span></td>
              <td class="p-4"><span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold ring-1 ring-inset" :class="meta(log.status).classes"><span class="h-2 w-2 rounded-full" :class="meta(log.status).dot" />{{ meta(log.status).label }}</span><span class="mt-1 block text-[11px] text-slate-500">Percobaan: {{ log.attempts }}</span></td>
              <td class="p-4 text-xs text-slate-600"><span class="block">Dibuat: {{ dateTime(log.created_at) }}</span><span class="mt-1 block font-semibold text-emerald-700">Terkirim: {{ dateTime(log.sent_at) }}</span></td>
              <td class="max-w-xs p-4 text-xs"><span v-if="log.last_error" class="block rounded-lg bg-red-50 p-2 font-semibold leading-5 text-red-700">{{ log.last_error }}</span><span v-else class="text-slate-400">Tidak ada kendala.</span></td>
              <td class="p-4 text-right"><button v-if="['queued','failed','skipped'].includes(log.status)" :disabled="retrying === log.id" class="rounded-lg border border-emerald-300 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50 disabled:opacity-50" @click="retry(log.id)">{{ retrying === log.id ? 'Mengirim…' : 'Kirim Ulang' }}</button><span v-else class="font-bold text-emerald-600">✓ Selesai</span></td>
            </tr>
            <tr v-if="!logs.data.length"><td colspan="7" class="p-12 text-center text-slate-500">Belum ada riwayat pengiriman pesan.</td></tr>
          </tbody>
        </table>
      </div>
      <div v-if="logs.links?.length > 3" class="mt-5 flex flex-wrap gap-2"><Link v-for="link in logs.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="rounded-lg border bg-white px-3 py-2 text-sm" :class="{'bg-emerald-800 text-white':link.active,'pointer-events-none opacity-40':!link.url}" /></div>
    </section>
  </main>
</template>
