<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
const props = defineProps<{ applicants: any; filters: any; registrationYears: number[] }>();
const filters = reactive({ ...props.filters });
const page = usePage() as any;
const role = computed(() => page.props.auth?.user?.role || 'viewer');
const saving = ref('');
const paymentEditor = reactive({ applicantId: '', applicantName: '', status: '', reason: '', error: '' });
const selectionEditor = reactive({ applicantId: '', applicantName: '', status: '', date: '', time: '', error: '' });
const selectedApplicants = ref<string[]>([]);
const bulkScheduleEditor = reactive({ open: false, date: '', time: '', error: '' });
const search = () => router.get('/admin/applicants', filters, { preserveState: true, replace: true });
const statuses: Record<string, { label: string; classes: string }> = {
  unpaid: { label: 'Belum Bayar', classes: 'bg-red-100 text-red-700 ring-red-200' },
  pending: { label: 'Verifikasi Pembayaran', classes: 'bg-amber-100 text-amber-800 ring-amber-200' },
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
  payment: ['unpaid', 'pending', 'paid', 'failed', 'expired', 'refunded'],
  document: ['pending_review', 'complete', 'incomplete', 'revision_submitted'],
  selection: ['not_scheduled', 'scheduled', 'attending_test', 'passed', 'not_passed', 'withdrawn'],
};
const canEdit = (dimension: string) => ({
  payment: ['super_admin', 'admin_pmb', 'finance'],
  document: ['super_admin', 'admin_pmb', 'reviewer'],
  selection: ['super_admin', 'admin_pmb'],
}[dimension]?.includes(role.value) ?? false);
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
      <form class="mt-6 grid gap-3 rounded-2xl bg-white p-4 md:grid-cols-5" @submit.prevent="search">
        <input v-model="filters.search" placeholder="Nama / nomor pendaftaran" class="rounded-xl border-slate-300" />
        <select v-model="filters.registration_year" class="rounded-xl border-slate-300"><option value="">Semua tahun pendaftaran</option><option v-for="year in registrationYears" :key="year" :value="year">Tahun {{ year }}</option></select>
        <select v-model="filters.payment_status" class="rounded-xl border-slate-300"><option value="">Semua pembayaran</option><option value="unpaid">Belum bayar</option><option value="pending">Menunggu</option><option value="paid">Lunas</option><option value="failed">Gagal</option></select>
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
        <table class="w-full min-w-[980px] text-left text-sm">
          <thead class="bg-emerald-950 text-white"><tr><th class="p-4">Nomor</th><th class="p-4"><label class="flex cursor-pointer items-center gap-2"><input type="checkbox" :checked="allSelectableChecked" class="rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500" aria-label="Pilih semua pendaftar yang dapat dijadwalkan" @change="toggleAllApplicants" />Nama</label></th><th class="p-4">Status Pendaftaran</th><th class="p-4">Pembayaran</th><th class="p-4">Berkas</th><th class="p-4">Seleksi</th><th class="p-4 text-right">Aksi</th></tr></thead>
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
              <td class="p-4 text-right"><Link :href="`/admin/applicants/${a.id}`" class="inline-flex items-center rounded-lg bg-emerald-800 px-4 py-2 font-bold text-white transition hover:bg-emerald-700">Detail →</Link></td>
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
