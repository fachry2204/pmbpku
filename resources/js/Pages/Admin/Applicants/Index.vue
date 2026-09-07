<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
const props = defineProps<{ applicants: any; filters: any; registrationYears: number[]; statusSummary: Record<string, number> }>();
const filters = reactive({ ...props.filters, registration_status: props.filters?.registration_status || '' });
const page = usePage() as any;
const role = computed(() => page.props.auth?.user?.role || 'viewer');
const saving = ref('');
const notifying = ref('');
const paymentEditor = reactive({ applicantId: '', applicantName: '', status: '', reason: '', error: '' });
const selectionEditor = reactive({ applicantId: '', applicantName: '', status: '', date: '', time: '', error: '' });
const selectedApplicants = ref<string[]>([]);
const bulkScheduleEditor = reactive({ open: false, date: '', time: '', error: '' });
const search = () => router.get('/admin/applicants', filters, { preserveState: true, replace: true });
const statusCards = computed(() => [
  { key: '', label: 'Semua Pendaftar', count: props.statusSummary.all ?? 0, description: 'Seluruh data masuk', icon: 'M8 7a4 4 0 1 0 8 0 4 4 0 0 0-8 0M4 21a8 8 0 0 1 16 0', tone: 'slate' },
  { key: 'not_paid', label: 'Belum Bayar', count: props.statusSummary.not_paid ?? 0, description: 'Menunggu pembayaran', icon: 'M3 7h18v12H3zM3 10h18M7 15h3', tone: 'red' },
  { key: 'paid', label: 'Sudah Bayar', count: props.statusSummary.paid ?? 0, description: 'Pembayaran diterima', icon: 'm5 12 4 4L19 6', tone: 'emerald' },
  { key: 'documents_complete', label: 'Berkas Lengkap', count: props.statusSummary.documents_complete ?? 0, description: 'Dokumen telah lengkap', icon: 'M6 2h9l5 5v15H6zM14 2v6h6M9 14l2 2 4-5', tone: 'blue' },
  { key: 'selection_stage', label: 'Tahap Seleksi', count: props.statusSummary.selection_stage ?? 0, description: 'Sedang proses seleksi', icon: 'M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20M12 6v6l4 2', tone: 'amber' },
  { key: 'selection_passed', label: 'Lulus Seleksi', count: props.statusSummary.selection_passed ?? 0, description: 'Dinyatakan diterima', icon: 'M12 15 8.5 17l1-4-3-2.5 4-.5L12 6l1.5 4 4 .5-3 2.5 1 4zM5 4h14', tone: 'violet' },
]);
const cardClasses: Record<string, { idle: string; active: string; icon: string }> = {
  slate: { idle: 'border-slate-200 hover:border-slate-400 hover:bg-slate-50', active: 'border-slate-700 bg-slate-800 text-white shadow-slate-200', icon: 'bg-slate-100 text-slate-700' },
  red: { idle: 'border-red-200 hover:border-red-400 hover:bg-red-50', active: 'border-red-600 bg-red-600 text-white shadow-red-200', icon: 'bg-red-100 text-red-700' },
  emerald: { idle: 'border-emerald-200 hover:border-emerald-400 hover:bg-emerald-50', active: 'border-emerald-700 bg-emerald-700 text-white shadow-emerald-200', icon: 'bg-emerald-100 text-emerald-700' },
  blue: { idle: 'border-blue-200 hover:border-blue-400 hover:bg-blue-50', active: 'border-blue-700 bg-blue-700 text-white shadow-blue-200', icon: 'bg-blue-100 text-blue-700' },
  amber: { idle: 'border-amber-200 hover:border-amber-400 hover:bg-amber-50', active: 'border-amber-500 bg-amber-500 text-white shadow-amber-200', icon: 'bg-amber-100 text-amber-700' },
  violet: { idle: 'border-violet-200 hover:border-violet-400 hover:bg-violet-50', active: 'border-violet-700 bg-violet-700 text-white shadow-violet-200', icon: 'bg-violet-100 text-violet-700' },
};
const filterByRegistrationStatus = (key: string) => {
  filters.registration_status = key;
  selectedApplicants.value = [];
  router.get('/admin/applicants', filters, { preserveState: true, preserveScroll: true, replace: true });
};
const statuses: Record<string, { label: string; classes: string }> = {
  unpaid: { label: 'Belum Bayar', classes: 'bg-red-100 text-red-700 ring-red-200' },
  // Data lama yang masih bernilai pending tetap ditampilkan sebagai Belum Bayar.
  pending: { label: 'Belum Bayar', classes: 'bg-red-100 text-red-700 ring-red-200' },
  paid: { label: 'Lunas', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
  failed: { label: 'Gagal', classes: 'bg-red-100 text-red-700 ring-red-200' },
  expired: { label: 'Kedaluwarsa', classes: 'bg-slate-200 text-slate-700 ring-slate-300' },
  refunded: { label: 'Dikembalikan', classes: 'bg-violet-100 text-violet-700 ring-violet-200' },
  pending_review: { label: 'Menunggu Review', classes: 'bg-amber-100 text-amber-800 ring-amber-200' },
  complete: { label: 'Berkas Lengkap', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
  incomplete: { label: 'Belum Lengkap', classes: 'bg-red-100 text-red-700 ring-red-200' },
  revision_submitted: { label: 'Perbaikan Dikirim', classes: 'bg-blue-100 text-blue-700 ring-blue-200' },
  not_scheduled: { label: 'Belum Dijadwalkan', classes: 'bg-slate-100 text-slate-700 ring-slate-200' },
  scheduled: { label: 'Dijadwalkan', classes: 'bg-blue-100 text-blue-700 ring-blue-200' },
  attending_test: { label: 'Mengikuti Seleksi', classes: 'bg-cyan-100 text-cyan-700 ring-cyan-200' },
  passed: { label: 'Diterima', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
  not_passed: { label: 'Tidak Diterima', classes: 'bg-red-100 text-red-700 ring-red-200' },
  withdrawn: { label: 'Dibatalkan', classes: 'bg-slate-200 text-slate-700 ring-slate-300' },
  not_paid: { label: 'Belum Bayar', classes: 'bg-red-100 text-red-700 ring-red-200' },
  documents_complete: { label: 'Berkas Lengkap', classes: 'bg-emerald-100 text-emerald-700 ring-emerald-200' },
  selection_stage: { label: 'Tahap Seleksi', classes: 'bg-blue-100 text-blue-700 ring-blue-200' },
  selection_passed: { label: 'Lulus Seleksi', classes: 'bg-emerald-700 text-white ring-emerald-700' },
};
const status = (value: string) => statuses[value] || { label: value?.replaceAll('_', ' ') || '-', classes: 'bg-slate-100 text-slate-700 ring-slate-200' };
const options: Record<string, string[]> = {
  payment: ['unpaid', 'paid', 'failed', 'expired', 'refunded'],
  document: ['pending_review', 'complete', 'incomplete', 'revision_submitted'],
  selection: ['not_scheduled', 'scheduled', 'attending_test', 'passed', 'not_passed', 'withdrawn'],
};
const canEdit = (dimension: string) => ({
  payment: ['super_admin', 'admin_pmb', 'finance'],
  document: ['super_admin', 'admin_pmb', 'reviewer'],
  selection: ['super_admin', 'admin_pmb'],
}[dimension]?.includes(role.value) ?? false);
const canSendNotification = computed(() => ['super_admin', 'admin_pmb', 'finance'].includes(role.value));
const sendCurrentStatusNotification = (applicant: any) => {
  if (notifying.value) return;
  notifying.value = applicant.id;
  router.post(`/admin/applicants/${applicant.id}/notifications/current-status`, {}, {
    preserveScroll: true,
    onFinish: () => { notifying.value = ''; },
  });
};
const updateStatus = (applicant: any, dimension: string, value: string) => {
  if (value === applicant[`${dimension}_status`]) return;
  if (dimension === 'payment') {
    Object.assign(paymentEditor, { applicantId: applicant.id, applicantName: applicant.full_name, status: value, reason: '', error: '' });
    return;
  }
  if (dimension === 'selection' && value === 'scheduled') {
    Object.assign(selectionEditor, { applicantId: applicant.id, applicantName: applicant.full_name, status: value, date: '', time: '', error: '' });
    return;
  }
  saving.value = `${applicant.id}:${dimension}`;
  router.patch(`/admin/applicants/${applicant.id}/status`, { dimension, status: value }, {
    preserveScroll: true,
    onError: () => router.reload({ only: ['applicants'] }),
    onFinish: () => { saving.value = ''; },
  });
};
const closeSelectionEditor = () => {
  const applicantId = selectionEditor.applicantId;
  Object.assign(selectionEditor, { applicantId: '', applicantName: '', status: '', date: '', time: '', error: '' });
  if (applicantId) router.reload({ only: ['applicants'] });
};
const saveSelectionSchedule = () => {
  if (!selectionEditor.date || !selectionEditor.time) { selectionEditor.error = 'Tanggal dan jam seleksi wajib dipilih.'; return; }
  saving.value = `${selectionEditor.applicantId}:selection`;
  router.patch(`/admin/applicants/${selectionEditor.applicantId}/status`, {
    dimension: 'selection', status: selectionEditor.status,
    selection_date: selectionEditor.date, selection_time: selectionEditor.time,
  }, {
    preserveScroll: true,
    onSuccess: () => Object.assign(selectionEditor, { applicantId: '', applicantName: '', status: '', date: '', time: '', error: '' }),
    onError: (errors) => { selectionEditor.error = String(errors.selection_date || errors.selection_time || errors.status || 'Jadwal seleksi gagal disimpan.'); },
    onFinish: () => { saving.value = ''; },
  });
};
const selectionSchedule = (applicant: any) => applicant.test_sessions?.[0]?.starts_at
  ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(applicant.test_sessions[0].starts_at))
  : '';
const selectableApplicants = computed(() => props.applicants.data.filter((applicant: any) => ['not_scheduled', 'scheduled'].includes(applicant.selection_status)));
const allSelectableChecked = computed(() => selectableApplicants.value.length > 0 && selectableApplicants.value.every((applicant: any) => selectedApplicants.value.includes(applicant.id)));
const toggleAllApplicants = () => {
  const pageIds = selectableApplicants.value.map((applicant: any) => applicant.id);
  selectedApplicants.value = allSelectableChecked.value
    ? selectedApplicants.value.filter(id => !pageIds.includes(id))
    : [...new Set([...selectedApplicants.value, ...pageIds])];
};
const openBulkSchedule = () => {
  if (!selectedApplicants.value.length) return;
  Object.assign(bulkScheduleEditor, { open: true, date: '', time: '', error: '' });
};
const closeBulkSchedule = () => Object.assign(bulkScheduleEditor, { open: false, date: '', time: '', error: '' });
const saveBulkSchedule = () => {
  if (!bulkScheduleEditor.date || !bulkScheduleEditor.time) { bulkScheduleEditor.error = 'Tanggal dan jam seleksi wajib dipilih.'; return; }
  saving.value = 'bulk-schedule';
  router.post('/admin/applicants/bulk-schedule', {
    applicant_ids: selectedApplicants.value,
    selection_date: bulkScheduleEditor.date,
    selection_time: bulkScheduleEditor.time,
  }, {
    preserveScroll: true,
    onSuccess: () => { selectedApplicants.value = []; closeBulkSchedule(); },
    onError: (errors) => { bulkScheduleEditor.error = String(errors.applicant_ids || errors.selection_date || errors.selection_time || 'Jadwal seleksi gagal disimpan.'); },
    onFinish: () => { saving.value = ''; },
  });
};
const closePaymentEditor = () => Object.assign(paymentEditor, { applicantId: '', applicantName: '', status: '', reason: '', error: '' });
const savePayment = () => {
  if (!paymentEditor.reason.trim()) { paymentEditor.error = 'Alasan perubahan pembayaran wajib diisi.'; return; }
  saving.value = `${paymentEditor.applicantId}:payment`;
  router.patch(`/admin/applicants/${paymentEditor.applicantId}/status`, { dimension: 'payment', status: paymentEditor.status, reason: paymentEditor.reason }, {
    preserveScroll: true,
    onSuccess: closePaymentEditor,
    onError: (errors) => { paymentEditor.error = String(errors.reason || errors.status || 'Status gagal diperbarui.'); },
    onFinish: () => { saving.value = ''; },
  });
};
</script>

<template>
  <Head title="Data Pendaftar" />
  <main class="min-h-screen bg-slate-100 p-5">
    <section class="mx-auto max-w-7xl">
      <Link href="/admin/dashboard" class="text-sm text-emerald-700">← Dashboard</Link>
      <h1 class="text-3xl font-bold text-emerald-950">Data pendaftar</h1>
      <div v-if="page.props.flash?.success" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-800">{{ page.props.flash.success }}</div>
      <div v-if="page.props.flash?.error" role="alert" class="mt-4 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-bold text-red-800"><span aria-hidden="true">!</span>{{ page.props.flash.error }}</div>
      <section class="mt-6" aria-labelledby="status-summary-heading">
        <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
          <div><h2 id="status-summary-heading" class="text-lg font-extrabold text-white">Ringkasan status pendaftar</h2><p class="text-xs text-emerald-100">Klik kartu untuk menyaring tabel berdasarkan tahapan pendaftaran.</p></div>
          <button v-if="filters.registration_status" type="button" class="rounded-lg border border-white/30 bg-white/10 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-white/20" @click="filterByRegistrationStatus('')">Hapus filter status</button>
        </div>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
          <button v-for="card in statusCards" :key="card.key || 'all'" type="button" :aria-pressed="filters.registration_status === card.key" :aria-label="`Tampilkan ${card.label}: ${card.count} pendaftar`" class="group min-h-[132px] rounded-2xl border p-4 text-left shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-emerald-950" :class="[filters.registration_status === card.key ? cardClasses[card.tone].active : cardClasses[card.tone].idle, filters.registration_status === card.key ? '' : 'bg-white text-slate-900']" @click="filterByRegistrationStatus(card.key)">
            <div class="flex items-start justify-between gap-2"><span class="grid h-10 w-10 place-items-center rounded-xl transition" :class="filters.registration_status === card.key ? 'bg-white/20 text-white' : cardClasses[card.tone].icon"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path :d="card.icon" /></svg></span><span v-if="filters.registration_status === card.key" class="rounded-full bg-white/20 px-2 py-1 text-[10px] font-black uppercase tracking-wide">Aktif</span></div>
            <p class="mt-4 text-2xl font-black">{{ card.count }}</p><p class="mt-0.5 text-sm font-extrabold">{{ card.label }}</p><p class="mt-1 text-[11px]" :class="filters.registration_status === card.key ? 'text-white/75' : 'text-slate-500'">{{ card.description }}</p>
          </button>
        </div>
      </section>
      <form class="mt-6 grid gap-3 rounded-2xl bg-white p-4 md:grid-cols-5" @submit.prevent="search">
        <input v-model="filters.search" placeholder="Nama / nomor pendaftaran" class="rounded-xl border-slate-300" />
        <select v-model="filters.registration_year" class="rounded-xl border-slate-300"><option value="">Semua tahun pendaftaran</option><option v-for="year in registrationYears" :key="year" :value="year">Tahun {{ year }}</option></select>
        <select v-model="filters.payment_status" class="rounded-xl border-slate-300"><option value="">Semua pembayaran</option><option value="unpaid">Belum bayar</option><option value="paid">Lunas</option><option value="failed">Gagal</option></select>
        <select v-model="filters.document_status" class="rounded-xl border-slate-300"><option value="">Semua berkas</option><option value="pending_review">Menunggu review</option><option value="complete">Lengkap</option><option value="incomplete">Belum lengkap</option></select>
        <button class="rounded-xl bg-emerald-800 py-3 font-bold text-white">Filter</button>
      </form>
      <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-white p-4 shadow-sm">
        <div><p class="font-extrabold text-emerald-950">Proses pendaftaran massal</p><p class="text-xs text-slate-500">Pilih calon mahasiswa pada tabel, lalu jadwalkan seleksi sekaligus.</p></div>
        <button type="button" :disabled="!selectedApplicants.length" class="rounded-xl bg-emerald-800 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40" @click="openBulkSchedule">Proses Pendaftaran <span v-if="selectedApplicants.length">({{ selectedApplicants.length }})</span></button>
      </div>
      <div v-if="bulkScheduleEditor.open" class="mt-4 rounded-2xl border border-blue-200 bg-white p-5 shadow-lg">
        <div class="flex items-start gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-100 font-black text-blue-700">⌚</span><div><h3 class="font-extrabold text-slate-900">Jadwalkan {{ selectedApplicants.length }} peserta</h3><p class="mt-1 text-sm text-slate-500">Tanggal dan waktu yang dipilih berlaku untuk seluruh calon mahasiswa terpilih.</p></div></div>
        <div class="mt-4 grid gap-4 md:grid-cols-2"><label><span class="text-sm font-bold text-slate-700">Tanggal seleksi <b class="text-red-600">*</b></span><input v-model="bulkScheduleEditor.date" type="date" :min="new Date().toISOString().slice(0, 10)" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500" /></label><label><span class="text-sm font-bold text-slate-700">Waktu seleksi <b class="text-red-600">*</b></span><input v-model="bulkScheduleEditor.time" type="time" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500" /></label></div>
        <p v-if="bulkScheduleEditor.error" class="mt-2 text-sm font-semibold text-red-700">{{ bulkScheduleEditor.error }}</p>
        <div class="mt-4 flex justify-end gap-3"><button type="button" class="rounded-xl border border-slate-300 px-4 py-2 font-bold text-slate-600" @click="closeBulkSchedule">Batal</button><button type="button" :disabled="saving === 'bulk-schedule'" class="rounded-xl bg-blue-700 px-5 py-2 font-bold text-white hover:bg-blue-600 disabled:opacity-50" @click="saveBulkSchedule">{{ saving === 'bulk-schedule' ? 'Memproses…' : 'Jadwalkan Seleksi' }}</button></div>
      </div>
      <div class="mt-5 overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[1120px] text-left text-sm">
          <thead class="bg-emerald-950 text-white"><tr><th class="p-4 text-white">Nomor</th><th class="p-4 text-white"><label class="flex cursor-pointer items-center gap-2 text-white"><input type="checkbox" :checked="allSelectableChecked" class="rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500" aria-label="Pilih semua pendaftar yang dapat dijadwalkan" @change="toggleAllApplicants" /> <span class="text-white">Nama</span></label></th><th class="p-4 text-white">Status Pendaftaran</th><th class="p-4 text-white">Pembayaran</th><th class="p-4 text-white">Berkas</th><th class="p-4 text-white">Seleksi</th><th class="p-4 text-right text-white">Aksi</th></tr></thead>
          <tbody>
            <template v-for="a in applicants.data" :key="a.id">
            <tr class="border-b transition hover:bg-emerald-50/40">
              <td class="p-4 font-bold text-emerald-800">{{ a.registration_number }}</td><td class="p-4 font-semibold text-slate-800"><label class="flex items-center gap-3" :class="{'cursor-pointer': ['not_scheduled', 'scheduled'].includes(a.selection_status)}"><input v-if="['not_scheduled', 'scheduled'].includes(a.selection_status)" v-model="selectedApplicants" type="checkbox" :value="a.id" class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" :aria-label="`Pilih ${a.full_name}`" />{{ a.full_name }}</label></td>
              <td class="p-4"><span class="inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-extrabold ring-1 ring-inset" :class="status(a.registration_status.key).classes">{{ a.registration_status.label }}</span></td>
              <td v-for="dimension in ['payment', 'document', 'selection']" :key="dimension" class="p-4">
                <select v-if="canEdit(dimension)" :value="a[`${dimension}_status`]" :disabled="saving === `${a.id}:${dimension}`" class="cursor-pointer rounded-full border-0 py-1 pl-3 pr-8 text-xs font-bold ring-1 ring-inset focus:ring-2 focus:ring-emerald-600 disabled:opacity-50" :class="status(a[`${dimension}_status`]).classes" @change="updateStatus(a, dimension, ($event.target as HTMLSelectElement).value)">
                  <option v-for="value in options[dimension]" :key="value" :value="value">{{ status(value).label }}</option>
                </select>
                <span v-else class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset" :class="status(a[`${dimension}_status`]).classes">{{ status(a[`${dimension}_status`]).label }}</span>
                <span v-if="dimension === 'selection' && selectionSchedule(a)" class="mt-1.5 block whitespace-nowrap text-[11px] font-semibold text-slate-500">{{ selectionSchedule(a) }}</span>
              </td>
              <td class="p-4"><div class="flex items-center justify-end gap-2">
                <button v-if="canSendNotification" type="button" :disabled="Boolean(notifying)" :aria-label="`Kirim ulang notifikasi status ${a.full_name}`" :title="`Kirim status terbaru kepada ${a.full_name}`" class="inline-flex min-w-[128px] items-center justify-center gap-2 rounded-lg border border-emerald-700 bg-white px-3 py-2 font-bold text-emerald-800 transition hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 disabled:cursor-wait disabled:opacity-50" @click="sendCurrentStatusNotification(a)">
                  <svg v-if="notifying !== a.id" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/><path d="m15.5 4.5 2-2"/></svg>
                  <svg v-else class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3"/><path class="opacity-75" d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                  {{ notifying === a.id ? 'Mengirim…' : 'Kirim Notif' }}
                </button>
                <Link :href="`/admin/applicants/${a.id}`" class="inline-flex items-center rounded-lg bg-emerald-800 px-4 py-2 font-bold text-white transition hover:bg-emerald-700">Detail →</Link>
              </div></td>
            </tr>
            <tr v-if="selectionEditor.applicantId === a.id" class="border-b bg-blue-50/70">
              <td colspan="7" class="p-5">
                <div class="ml-auto max-w-3xl rounded-2xl border border-blue-200 bg-white p-5 shadow-sm">
                  <div class="flex items-start gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-blue-100 font-black text-blue-700">⌚</span><div><h3 class="font-extrabold text-slate-900">Atur jadwal seleksi</h3><p class="mt-1 text-sm text-slate-500">Pilih tanggal dan jam seleksi untuk <b>{{ selectionEditor.applicantName }}</b>. Keduanya wajib diisi sebelum status menjadi Dijadwalkan.</p></div></div>
                  <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label><span class="text-sm font-bold text-slate-700">Tanggal seleksi <b class="text-red-600">*</b></span><input v-model="selectionEditor.date" type="date" :min="new Date().toISOString().slice(0, 10)" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500" /></label>
                    <label><span class="text-sm font-bold text-slate-700">Jam seleksi <b class="text-red-600">*</b></span><input v-model="selectionEditor.time" type="time" class="mt-2 w-full rounded-xl border-slate-300 focus:border-blue-500 focus:ring-blue-500" /></label>
                  </div>
                  <p v-if="selectionEditor.error" class="mt-2 text-sm font-semibold text-red-700">{{ selectionEditor.error }}</p>
                  <div class="mt-4 flex justify-end gap-3"><button type="button" class="rounded-xl border border-slate-300 px-4 py-2 font-bold text-slate-600" @click="closeSelectionEditor">Batal</button><button type="button" :disabled="saving === `${a.id}:selection`" class="rounded-xl bg-blue-700 px-5 py-2 font-bold text-white hover:bg-blue-600 disabled:opacity-50" @click="saveSelectionSchedule">{{ saving === `${a.id}:selection` ? 'Menyimpan…' : 'Simpan Jadwal' }}</button></div>
                </div>
              </td>
            </tr>
            <tr v-if="paymentEditor.applicantId === a.id" class="border-b bg-amber-50/70">
              <td colspan="7" class="p-5">
                <div class="ml-auto max-w-3xl rounded-2xl border border-amber-200 bg-white p-5 shadow-sm">
                  <div class="flex items-start gap-3"><span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-amber-100 font-black text-amber-700">!</span><div><h3 class="font-extrabold text-slate-900">Konfirmasi perubahan pembayaran manual</h3><p class="mt-1 text-sm text-slate-500">{{ paymentEditor.applicantName }} akan diubah menjadi <b>{{ status(paymentEditor.status).label }}</b>. Callback payment gateway berikutnya tetap dapat memperbarui status ini.</p></div></div>
                  <label class="mt-4 block"><span class="text-sm font-bold text-slate-700">Alasan perubahan <b class="text-red-600">*</b></span><textarea v-model="paymentEditor.reason" rows="3" maxlength="1000" placeholder="Contoh: Pembayaran diverifikasi langsung oleh bagian keuangan..." class="mt-2 w-full rounded-xl border-slate-300 focus:border-amber-500 focus:ring-amber-500"></textarea></label>
                  <p v-if="paymentEditor.error" class="mt-2 text-sm font-semibold text-red-700">{{ paymentEditor.error }}</p>
                  <div class="mt-4 flex justify-end gap-3"><button type="button" class="rounded-xl border border-slate-300 px-4 py-2 font-bold text-slate-600" @click="closePaymentEditor">Batal</button><button type="button" :disabled="saving === `${a.id}:payment`" class="rounded-xl bg-amber-600 px-5 py-2 font-bold text-white hover:bg-amber-500 disabled:opacity-50" @click="savePayment">{{ saving === `${a.id}:payment` ? 'Menyimpan…' : 'Simpan Perubahan' }}</button></div>
                </div>
              </td>
            </tr>
            </template>
            <tr v-if="!applicants.data.length"><td colspan="7" class="p-10 text-center text-slate-500">Belum ada data pendaftar.</td></tr>
          </tbody>
        </table>
      </div>
    </section>
  </main>
</template>
