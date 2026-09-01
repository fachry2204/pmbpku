<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps<{ applicants: any; filters: { search?: string }; scoreLabels: string[]; scoreWeights: number[] }>();
const search = reactive({ value: props.filters.search || '' });
const saving = reactive<Record<string, boolean>>({});

const find = () => router.get('/admin/applicant-scores', { search: search.value }, { preserveState: true, replace: true });
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

      <form class="mt-6 flex max-w-xl gap-3 rounded-2xl bg-white p-4 shadow-sm" @submit.prevent="find">
        <input v-model="search.value" class="min-w-0 flex-1 rounded-xl border-slate-300" placeholder="Cari nama atau nomor pendaftaran" />
        <button class="rounded-xl bg-emerald-800 px-5 font-bold text-white">Cari</button>
      </form>

      <div class="mt-5 overflow-x-auto rounded-2xl bg-white shadow-sm">
        <table class="w-full min-w-[980px] text-left text-sm">
          <thead class="bg-emerald-950 text-white">
            <tr><th class="p-4">Nama Pendaftar</th><th v-for="(label, index) in scoreLabels" :key="index" class="p-4 text-center"><span class="block">{{ label }}</span><span class="mt-1 block text-xs font-medium text-emerald-200">Bobot {{ scoreWeights[index] }}%</span></th><th class="p-4 text-center">Nilai Akhir</th><th class="p-4 text-center">Aksi</th></tr>
          </thead>
          <tbody>
            <tr v-for="applicant in applicants.data" :key="applicant.id" class="border-b border-slate-100 hover:bg-emerald-50/40">
              <td class="p-4"><b class="block text-emerald-950">{{ applicant.full_name }}</b><span class="text-xs text-slate-500">{{ applicant.registration_number }}</span></td>
              <td v-for="number in 4" :key="number" class="p-3"><input v-model="applicant[`score_${number}`]" type="number" min="0" max="100" step="0.01" class="w-24 rounded-lg border-slate-300 text-center" /></td>
              <td class="p-4 text-center"><span class="rounded-lg bg-amber-50 px-3 py-2 font-extrabold text-amber-800">{{ finalScore(applicant) }}</span></td>
              <td class="p-4 text-center"><button :disabled="saving[applicant.id]" class="rounded-lg bg-[#16866d] px-4 py-2 font-bold text-white disabled:opacity-50" @click="save(applicant)">{{ saving[applicant.id] ? 'Menyimpan...' : 'Simpan' }}</button></td>
            </tr>
            <tr v-if="!applicants.data.length"><td colspan="7" class="p-12 text-center text-slate-500">Belum ada pendaftar yang dinyatakan diterima.</td></tr>
          </tbody>
        </table>
      </div>

      <div v-if="applicants.links?.length > 3" class="mt-5 flex flex-wrap gap-2"><Link v-for="link in applicants.links" :key="link.label" :href="link.url || '#'" v-html="link.label" class="rounded-lg border bg-white px-3 py-2 text-sm" :class="{'bg-emerald-800 text-white':link.active,'pointer-events-none opacity-40':!link.url}" /></div>
    </section>
  </main>
</template>
