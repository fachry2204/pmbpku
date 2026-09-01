<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';

const props = defineProps<{ applicant: any }>();
const label = (value: string) => value.replaceAll('_', ' ');
const revision = (document: any, event: Event) => {
  const file = (event.target as HTMLInputElement).files?.[0];
  if (!file) return;
  const data = new FormData();
  data.append('document', file);
  router.post(`/cek-status/${props.applicant.id}/documents/${document.type}/revision`, data, { forceFormData: true, preserveScroll: true });
};
</script>

<template>
  <Head title="Status Pendaftaran" />
  <main class="min-h-screen bg-emerald-50 p-5">
    <section class="mx-auto max-w-4xl space-y-6 rounded-3xl bg-white p-8 shadow-xl">
      <div class="flex items-center gap-5">
        <img v-if="applicant.photo_url" :src="applicant.photo_url" :alt="`Pas foto ${applicant.full_name}`" class="h-24 w-20 rounded-2xl border-2 border-emerald-100 object-cover shadow-md" />
        <div><p class="font-semibold text-emerald-700">{{ applicant.registration_number }}</p><h1 class="text-3xl font-bold text-emerald-950">Status {{ applicant.full_name }}</h1></div>
      </div>
      <div class="rounded-2xl bg-gradient-to-r from-emerald-900 to-emerald-700 p-5 text-white shadow-lg">
        <p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-200">Status Pendaftaran</p>
        <p class="mt-2 text-2xl font-extrabold">{{ applicant.registration_status.label }}</p>
      </div>
      <div class="grid gap-4 md:grid-cols-3">
        <article v-for="[title, value] in [['Pembayaran', applicant.payment_status], ['Berkas', applicant.document_status], ['Seleksi', applicant.selection_status]]" :key="title" class="rounded-2xl bg-emerald-50 p-5"><p class="text-sm text-slate-500">{{ title }}</p><p class="mt-1 font-bold capitalize text-emerald-950">{{ label(value) }}</p></article>
      </div>
      <div><h2 class="text-xl font-bold">Status dokumen</h2><div class="mt-3 divide-y rounded-xl border"><div v-for="document in applicant.documents" :key="document.id" class="p-4"><div class="flex justify-between gap-4"><span class="capitalize">{{ label(document.type) }}</span><span class="font-semibold capitalize">{{ label(document.verification_status) }}</span></div><p v-if="document.review_note" class="mt-2 text-sm text-amber-800">{{ document.review_note }}</p><label v-if="document.verification_status === 'revision_required'" class="mt-3 block rounded-lg bg-amber-50 p-3 text-sm font-semibold text-amber-900">Unggah dokumen perbaikan<input type="file" accept=".jpg,.jpeg,.png,.pdf" class="mt-2 block w-full" @change="revision(document, $event)" /></label></div></div></div>
    </section>
  </main>
</template>
