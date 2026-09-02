<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{ applicant: any }>();
const page = usePage() as any;
const displayDate = (value: string) => { const m = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/); return m ? `${m[3]}/${m[2]}/${m[1]}` : ''; };
const databaseDate = (value: string) => { const m = value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/); return m ? `${m[3]}-${m[2]}-${m[1]}` : value; };
const form = useForm({ full_name: props.applicant.full_name, birth_place: props.applicant.birth_place, birth_date: displayDate(props.applicant.birth_date), address: props.applicant.address, email: props.applicant.email, whatsapp: props.applicant.whatsapp_display });
const maskDate = (event: Event) => { const input = event.target as HTMLInputElement; const digits = input.value.replace(/\D/g, '').slice(0, 8); input.value = digits.length > 4 ? `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}` : digits.length > 2 ? `${digits.slice(0, 2)}/${digits.slice(2)}` : digits; form.birth_date = input.value; };
const submit = () => form.transform(data => ({ ...data, birth_date: databaseDate(data.birth_date) })).put(`/admin/applicants/${props.applicant.id}`);
const documentTypes = [['recommendation_letter', 'Surat Rekomendasi'], ['diploma', 'Ijazah'], ['photo_4x6', 'Pas Foto 4×6'], ['identity_card', 'KTP'], ['pddikti_screenshot', 'Screenshot PDDIKTI']] as const;
const selectedFiles = reactive<Record<string, File | null>>({});
const uploading = ref('');
const uploadError = ref('');
const currentDocuments = computed(() => Object.fromEntries((props.applicant.documents || []).map((document: any) => [document.type, document])));
const chooseFile = (type: string, event: Event) => { selectedFiles[type] = (event.target as HTMLInputElement).files?.[0] || null; uploadError.value = ''; };
const uploadDocument = (type: string) => {
  const file = selectedFiles[type];
  if (!file) { uploadError.value = 'Pilih file yang akan diunggah terlebih dahulu.'; return; }
  const data = new FormData(); data.append('type', type); data.append('document', file);
  uploading.value = type;
  router.post(`/admin/applicants/${props.applicant.id}/documents`, data, { forceFormData: true, preserveScroll: true, onSuccess: () => { selectedFiles[type] = null; uploadError.value = ''; }, onError: errors => { uploadError.value = String(errors.document || errors.type || 'Dokumen gagal diunggah.'); }, onFinish: () => uploading.value = '' });
};
const documentStatus = (value: string) => ({ pending: 'Menunggu pemeriksaan', valid: 'Valid', revision_required: 'Perlu perbaikan' }[value] || value);
</script>

<template>
  <Head :title="`Edit ${applicant.full_name}`" />
  <main class="min-h-screen p-5">
    <section class="mx-auto max-w-4xl">
      <Link :href="`/admin/applicants/${applicant.id}`" class="font-semibold text-amber-300">← Kembali ke detail</Link>
      <div v-if="page.props.flash?.success" class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-bold text-emerald-800">{{ page.props.flash.success }}</div>

      <div class="mt-4 rounded-2xl bg-white p-7 shadow-sm">
        <p class="font-semibold text-emerald-700">{{ applicant.registration_number }}</p><h1 class="text-3xl font-bold text-emerald-950">Edit Data Pendaftar</h1>
        <form class="mt-7 grid gap-5 md:grid-cols-2" @submit.prevent="submit">
          <label><b>Nama lengkap</b><input v-model="form.full_name" class="mt-2 w-full rounded-xl border-slate-300" /><small class="text-red-700">{{ form.errors.full_name }}</small></label>
          <label><b>Tempat lahir</b><input v-model="form.birth_place" class="mt-2 w-full rounded-xl border-slate-300" /><small class="text-red-700">{{ form.errors.birth_place }}</small></label>
          <label><b>Tanggal lahir</b><input v-model="form.birth_date" type="text" inputmode="numeric" maxlength="10" placeholder="DD/MM/YYYY" class="mt-2 w-full rounded-xl border-slate-300" @input="maskDate" /><small class="text-red-700">{{ form.errors.birth_date }}</small></label>
          <label><b>Email</b><input v-model="form.email" type="email" class="mt-2 w-full rounded-xl border-slate-300" /><small class="text-red-700">{{ form.errors.email }}</small></label>
          <label><b>Nomor WhatsApp</b><input v-model="form.whatsapp" class="mt-2 w-full rounded-xl border-slate-300" /><small class="text-red-700">{{ form.errors.whatsapp }}</small></label>
          <label class="md:col-span-2"><b>Alamat lengkap</b><textarea v-model="form.address" rows="4" class="mt-2 w-full rounded-xl border-slate-300"></textarea><small class="text-red-700">{{ form.errors.address }}</small></label>
          <div class="flex justify-end gap-3 border-t pt-5 md:col-span-2"><Link :href="`/admin/applicants/${applicant.id}`" class="rounded-xl border px-6 py-3 font-bold text-slate-700">Batal</Link><button :disabled="form.processing" class="rounded-xl bg-emerald-800 px-7 py-3 font-bold text-white disabled:opacity-50">{{ form.processing ? 'Menyimpan…' : 'Simpan Perubahan' }}</button></div>
        </form>
      </div>

      <div class="mt-5 rounded-2xl bg-white p-7 shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Berkas Pendaftar</p><h2 class="mt-1 text-2xl font-extrabold text-emerald-950">Dokumen Pendaftaran</h2><p class="mt-1 text-sm text-slate-500">Unggah dokumen yang belum tersedia atau ganti dokumen lama. Maksimal 5 MB dengan format JPG, PNG, atau PDF.</p>
        <p v-if="uploadError" class="mt-4 rounded-xl bg-red-50 px-4 py-3 font-semibold text-red-700">{{ uploadError }}</p>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
          <article v-for="([type, label]) in documentTypes" :key="type" class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
            <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h3 class="font-extrabold text-slate-800">{{ label }}</h3><template v-if="currentDocuments[type]"><p class="mt-1 truncate text-xs text-slate-500">{{ currentDocuments[type].original_name }}</p><span class="mt-2 inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold text-amber-700 ring-1 ring-amber-200">{{ documentStatus(currentDocuments[type].verification_status) }}</span></template><span v-else class="mt-2 inline-flex rounded-full bg-slate-200 px-2.5 py-1 text-[10px] font-bold text-slate-600">Belum diunggah</span></div><a v-if="currentDocuments[type]" :href="`/admin/documents/${currentDocuments[type].id}/download`" class="rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs font-bold text-emerald-700">Unduh</a></div>
            <label class="mt-4 block cursor-pointer rounded-xl border border-dashed border-emerald-300 bg-white p-3 text-center text-xs font-bold text-emerald-700 hover:bg-emerald-50">{{ selectedFiles[type]?.name || 'Pilih file' }}<input type="file" accept=".jpg,.jpeg,.png,.pdf" class="sr-only" @change="chooseFile(type, $event)" /></label>
            <button type="button" :disabled="!selectedFiles[type] || uploading === type" class="mt-3 w-full rounded-xl bg-emerald-800 py-2.5 text-xs font-extrabold text-white disabled:cursor-not-allowed disabled:opacity-40" @click="uploadDocument(type)">{{ uploading === type ? 'Mengunggah…' : currentDocuments[type] ? 'Ganti Dokumen' : 'Unggah Dokumen' }}</button>
          </article>
        </div>
      </div>
    </section>
  </main>
</template>
