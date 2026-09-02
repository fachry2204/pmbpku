<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps<{ applicants: any; filters: { search?: string; registration_year?: string | number }; registrationYears: number[]; scoreLabels: string[]; scoreWeights: number[] }>();
const search = reactive({ value: props.filters.search || '', registrationYear: props.filters.registration_year || '' });
const saving = reactive<Record<string, boolean>>({});

const find = () => router.get('/admin/applicant-scores', { search: search.value, registration_year: search.registrationYear }, { preserveState: true, replace: true });
const finalScore = (applicant: any) => {
  const values = [applicant.score_1, applicant.score_2, applicant.score_3, applicant.score_4];
  if (values.some(value => value === null || value === '' || value === undefined)) return '—';
  return values.reduce((total, value, index) => total + (Number(value) * props.scoreWeights[index] / 100), 0).toFixed(2);
};
const save = (applicant: any) => {
  saving[applicant.id] = true;
  router.patch(`/admin/applicant-scores/${applicant.id}`, {
    score_1: applicant.score_1,
    score_2: applicant.score_2,
    score_3: applicant.score_3,
    score_4: applicant.score_4,
  }, { preserveScroll: true, onFinish: () => saving[applicant.id] = false });
};
</script>

<template>
  <Head title="Nilai Calon" />
  <main class="min-h-screen p-5 md:p-8">
    <section class="mx-auto max-w-7xl">
      <div>
        <p class="text-xs font-bold uppercase tracking-[.2em] text-[#b38b21]">Seleksi Penerimaan</p>
        <h1 class="mt-1 text-3xl font-extrabold text-emerald-950">Nilai Calon</h1>
        <p class="mt-2 text-sm text-slate-500">Nilai akhir dihitung otomatis berdasarkan bobot setiap komponen.</p>
        <div class="mt-3 flex flex-wrap gap-2">
          <span v-for="(label, index) in scoreLabels" :key="label" class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">{{ label }} · {{ scoreWeights[index] }}%</span>
        </div>
      </div>

      <form class="mt-6 grid max-w-3xl gap-3 rounded-2xl bg-white p-4 shadow-sm sm:grid-cols-[minmax(0,1fr)_220px_auto]" @submit.prevent="find">
        <input v-model="search.value" class="min-w-0 flex-1 rounded-xl border-slate-300" placeholder="Cari nama atau nomor pendaftaran" />
        <select v-model="search.registrationYear" class="rounded-xl border-slate-300"><option value="">Semua tahun pendaftaran</option><option v-for="year in registrationYears" :key="year" :value="year">Tahun {{ year }}</option></select>
        <button class="rounded-xl bg-emerald-800 px-5 font-bold text-white">Cari</button>
      </form>

      <div class="mt-5 overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[980px] text-left text-sm">
          <thead class="bg-emerald-950 text-white">
            <tr><th class="w-[150px] whitespace-normal p-3 leading-tight">Nama Pendaftar</th><th v-for="(label, index) in scoreLabels" :key="index" class="w-[170px] whitespace-normal p-3 text-center leading-tight"><span class="block break-words">{{ label }}</span><span class="mt-1 block text-xs font-medium text-emerald-200">Bobot {{ scoreWeights[index] }}%</span></th><th class="w-[110px] whitespace-normal p-3 text-center leading-tight">Nilai Akhir</th><th class="w-[90px] whitespace-normal p-3 text-center leading-tight">Aksi</th></tr>
          </thead>
          <tbody>
            <tr v-for="applicant in applicants.data" :key="applicant.id" class="border-b border-slate-100 hover:bg-emerald-50/40">
              <td class="p-4"><b class="block text-emerald-950">{{ applicant.full_name }}</b><span class="text-xs text-slate-500">{{ applicant.registration_number }}</span></td>
              <td v-for="number in 4" :key="number" class="p-3"><input v-model="applicant[`score_${number}`]" type="number" min="0" max="100" step="0.01" class="w-24 rounded-lg border-slate-300 text-center" /></td>
              <td class="p-4 text-center"><span class="rounded-lg bg-amber-50 px-3 py-2 font-extrabold text-amber-800">{{ finalScore(applicant) }}</span></td>
              <td class="p-4 text-center"><button :disabled="saving[applicant.id]" class="rounded-lg bg-[#16866d] px-4 py-2 font-bold text-white disabled:opacity-50" @click="save(applicant)">{{ saving[applicant.id] ? 'Menyimpan...' : 'Simpan' }}</button></td>
            </tr>
            <tr v-if="!applicants.data.length"><td colspan="7" class="p-12 text-center text-slate-500">Belum ada pendaftar yang sedang mengikuti tahap seleksi.</td></tr>
          </tbody>
        </table>
      </div>

      <div v-if="applicants.links?.length > 3" class="mt-5 flex flex-wrap gap-2"><Link v-for="link in applicants.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="rounded-lg border bg-white px-3 py-2 text-sm" :class="{'bg-emerald-800 text-white':link.active,'pointer-events-none opacity-40':!link.url}" /></div>
    </section>
  </main>
</template>
