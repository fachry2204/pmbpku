<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ applicant: { registration_number: string; full_name: string; payment_status: string } | null }>();
</script>

<template>
  <Head title="Pembayaran Sedang Diproses" />
  <main class="islamic-gradient-page grid min-h-screen place-items-center p-5">
    <section class="islamic-glass-card w-full max-w-xl rounded-[28px] p-8 text-center md:p-12">
      <div class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-amber-100 text-4xl text-amber-700">⏳</div>
      <p class="mt-6 text-sm font-bold uppercase tracking-[.2em] text-[#b38b21]">Verifikasi Pembayaran</p>
      <h1 class="mt-3 text-3xl font-extrabold text-[#064e3b]">Pembayaran sedang diproses</h1>
      <p class="mx-auto mt-4 max-w-md text-base leading-7 text-slate-600">Pembayaran Anda saat ini sedang diproses. Anda akan mendapatkan informasi jika pembayaran sudah berhasil diverifikasi oleh Admin.</p>
      <div v-if="applicant" class="my-7 rounded-2xl bg-[#064e3b] p-5 text-white">
        <p class="text-xs font-bold uppercase tracking-wider text-emerald-200">Nomor Pendaftaran</p>
        <p class="mt-2 text-2xl font-black tracking-wider">{{ applicant.registration_number }}</p>
      </div>
      <p class="text-sm text-slate-500">Status: <b class="text-amber-700">Verifikasi Pembayaran</b></p>
      <Link v-if="applicant" :href="`/cek-status?identifier=${encodeURIComponent(applicant.registration_number)}`" class="mt-7 inline-flex w-full items-center justify-center rounded-xl bg-[#064e3b] px-6 py-3 font-extrabold text-white transition hover:bg-[#087154]">Cek Status Pendaftaran →</Link>
      <Link href="/" class="mt-4 inline-block font-semibold text-emerald-800">Kembali ke Beranda</Link>
    </section>
  </main>
</template>
