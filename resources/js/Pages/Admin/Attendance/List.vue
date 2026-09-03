<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
  participants: Array<{
    id: string;
    registration_number: string;
    full_name: string;
    whatsapp: string;
    test_date: string | null;
    test_time: string | null;
    location: string;
    is_present: boolean;
    attendance_status: string;
    attended_at: string | null;
  }>;
  stats: { total: number; present: number; absent: number };
}>();
</script>

<template>
  <Head title="Absen Peserta" />
  <main class="min-h-screen px-4 py-7 sm:px-7">
    <section class="mx-auto max-w-7xl space-y-6">
      <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
          <p class="text-xs font-black uppercase tracking-[.24em] text-amber-300">Seleksi Penerimaan</p>
          <h1 class="mt-1 text-3xl font-black text-white">Absen Peserta</h1>
          <p class="mt-2 text-sm text-emerald-50/80">Pantau kehadiran seluruh calon mahasiswa yang sudah dijadwalkan mengikuti seleksi.</p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link href="/absen" class="rounded-xl border border-white/25 bg-white/10 px-5 py-3 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20">Buka Pemindai QR</Link>
          <a href="/admin/attendance/pdf" class="rounded-xl bg-amber-400 px-5 py-3 text-sm font-black text-emerald-950 shadow-lg shadow-amber-950/10 transition hover:bg-amber-300">Download PDF Absensi</a>
        </div>
      </header>

      <div class="grid gap-4 sm:grid-cols-3">
        <article class="stat-card"><span class="stat-icon bg-blue-50 text-blue-700">Σ</span><div><p>Jumlah Peserta</p><strong>{{ stats.total }}</strong></div></article>
        <article class="stat-card"><span class="stat-icon bg-emerald-50 text-emerald-700">✓</span><div><p>Jumlah Hadir</p><strong>{{ stats.present }}</strong></div></article>
        <article class="stat-card"><span class="stat-icon bg-amber-50 text-amber-700">!</span><div><p>Jumlah Tidak Hadir</p><strong>{{ stats.absent }}</strong></div></article>
      </div>

      <section class="overflow-hidden rounded-2xl border border-white/50 bg-white shadow-xl shadow-emerald-950/15">
        <div class="border-b border-slate-100 px-5 py-4">
          <h2 class="font-black text-emerald-950">Daftar Kehadiran Peserta</h2>
          <p class="mt-1 text-xs text-slate-500">Status berubah otomatis setelah petugas melakukan absensi melalui halaman pemindai.</p>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[850px] text-left">
            <thead class="bg-emerald-950 text-white">
              <tr><th>No.</th><th>Nomor Registrasi</th><th>Nama Peserta</th><th>No. HP</th><th>Jadwal & Lokasi</th><th>Status Kehadiran</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="(participant, index) in participants" :key="participant.id" class="transition hover:bg-emerald-50/50">
                <td class="text-slate-500">{{ index + 1 }}</td>
                <td class="font-black text-emerald-800">{{ participant.registration_number }}</td>
                <td class="font-bold text-slate-800">{{ participant.full_name }}</td>
                <td>{{ participant.whatsapp }}</td>
                <td><b class="block text-slate-700">{{ participant.test_date }} · {{ participant.test_time }}</b><span class="mt-1 block max-w-xs text-xs text-slate-500">{{ participant.location }}</span></td>
                <td><span class="inline-flex rounded-full px-3 py-1.5 text-xs font-black ring-1 ring-inset" :class="participant.is_present ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-amber-200'">{{ participant.attendance_status }}</span><small v-if="participant.attended_at" class="mt-1.5 block text-slate-500">{{ participant.attended_at }}</small></td>
              </tr>
              <tr v-if="!participants.length"><td colspan="6" class="px-5 py-16 text-center text-slate-500">Belum ada peserta yang dijadwalkan mengikuti seleksi.</td></tr>
            </tbody>
          </table>
        </div>
      </section>
    </section>
  </main>
</template>

<style scoped>
.stat-card{display:flex;align-items:center;gap:1rem;border:1px solid rgba(255,255,255,.65);border-radius:1rem;background:#fff;padding:1.15rem 1.25rem;box-shadow:0 14px 35px rgba(0,50,38,.13)}
.stat-card p{font-size:.75rem;font-weight:700;color:#64748b}.stat-card strong{display:block;margin-top:.2rem;font-size:1.65rem;line-height:1.8rem;color:#052e24}.stat-icon{display:grid;height:2.75rem;width:2.75rem;flex:none;place-items:center;border-radius:.85rem;font-size:1.15rem;font-weight:900}
th{padding:1rem 1.25rem;font-size:.7rem;text-transform:uppercase;letter-spacing:.045em}td{padding:1rem 1.25rem;font-size:.78rem;vertical-align:middle}
</style>
