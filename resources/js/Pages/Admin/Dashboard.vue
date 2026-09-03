<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ stats: Record<string, number> }>();
const sidebarOpen = ref(false);
const page = usePage();
const user = computed(() => page.props.auth?.user as { name?: string; email?: string; role?: string });

const menus = [
  { label: 'Dashboard', href: '/admin/dashboard', icon: 'grid' },
  { label: 'Data Pendaftar', href: '/admin/applicants', icon: 'users' },
  { label: 'Pembayaran', href: '/admin/payments', icon: 'card' },
  { label: 'Absen Peserta', href: '/admin/attendance', icon: 'check' },
  { label: 'Nilai Calon', href: '/admin/applicant-scores', icon: 'file' },
  { label: 'Pengguna', href: '/admin/users', icon: 'user' },
  { label: 'Pesan Terkirim', href: '/admin/notification-logs', icon: 'bell' },
  { label: 'Landing Page', href: '/admin/landing', icon: 'globe' },
  { label: 'Pengaturan', href: '/admin/settings', icon: 'settings' },
  { label: 'Audit Log', href: '/admin/audit-logs', icon: 'file' },
];

const cards = computed(() => [
  { label: 'Total Pendaftar', value: props.stats.total ?? 0, note: 'Seluruh pendaftar', tone: 'emerald', icon: 'users' },
  { label: 'Sudah Membayar', value: props.stats.paid ?? 0, note: 'Pembayaran terverifikasi', tone: 'blue', icon: 'check' },
  { label: 'Belum Membayar', value: props.stats.unpaid ?? 0, note: 'Menunggu pembayaran', tone: 'amber', icon: 'clock' },
  { label: 'Berkas Diproses', value: props.stats.pending_documents ?? 0, note: 'Perlu ditinjau', tone: 'violet', icon: 'file' },
]);

const rupiah = (value: number) => 'Rp ' + Number(value || 0).toLocaleString('id-ID');
</script>

<template>
  <Head title="Dashboard Admin" />
  <div class="islamic-light-bg min-h-screen text-slate-800">
    <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" @click="sidebarOpen=false" />

    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">
      <div class="flex h-20 items-center gap-3 border-b border-slate-100 px-6">
        <img src="/images/logo-pku-mui-jakarta.png" alt="Pendidikan Kader Ulama MUI Provinsi DKI Jakarta" class="h-11 max-w-full object-contain object-left" />
      </div>

      <div class="flex-1 overflow-y-auto px-3 py-5">
        <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-widest text-slate-400">Admin</p>
        <nav class="space-y-1">
          <Link v-for="menu in menus" :key="menu.href" :href="menu.href" :class="menu.href === '/admin/dashboard' ? 'bg-[#16866d] text-white shadow-md shadow-emerald-900/15' : 'text-slate-600 hover:bg-emerald-50 hover:text-[#087154]'" class="group flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold transition">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
              <path v-if="menu.icon==='grid'" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" />
              <path v-else-if="menu.icon==='users'" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
              <path v-else-if="menu.icon==='card'" d="M3 6h18v12H3zM3 10h18M7 15h2" />
              <path v-else-if="menu.icon==='calendar'" d="M3 5h18v16H3zM7 3v4M17 3v4M3 10h18" />
              <path v-else-if="menu.icon==='check'" d="M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
              <path v-else-if="menu.icon==='user'" d="M20 21a8 8 0 0 0-16 0M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10" />
              <path v-else-if="menu.icon==='bell'" d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
              <path v-else-if="menu.icon==='globe'" d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20M2 12h20M12 2c3 3 3 17 0 20M12 2c-3 3-3 17 0 20" />
              <path v-else-if="menu.icon==='settings'" d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06-2.83 2.83-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6 1.7 1.7 0 0 0-.4 1v.1h-4v-.1a1.7 1.7 0 0 0-1.1-1.6 1.7 1.7 0 0 0-1.88.34l-.06.06-2.83-2.83.06-.06A1.7 1.7 0 0 0 4.1 15a1.7 1.7 0 0 0-.6-1 1.7 1.7 0 0 0-1-.4h-.1v-4h.1A1.7 1.7 0 0 0 4.1 8a1.7 1.7 0 0 0-.34-1.88l-.06-.06 2.83-2.83.06.06A1.7 1.7 0 0 0 8.5 3.6a1.7 1.7 0 0 0 1-.6 1.7 1.7 0 0 0 .4-1v-.1h4V2a1.7 1.7 0 0 0 1.1 1.6 1.7 1.7 0 0 0 1.88-.34l.06-.06 2.83 2.83-.06.06A1.7 1.7 0 0 0 19.4 8c.13.37.35.7.6 1 .28.3.63.43 1 .5h.1v4H21a1.7 1.7 0 0 0-1.6 1.1z" />
              <path v-else d="M5 3h10l4 4v14H5zM14 3v5h5M8 13h8M8 17h6" />
            </svg>
            {{ menu.label }}
          </Link>
        </nav>
      </div>

      <div class="border-t border-slate-100 p-4">
        <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3"><span class="grid h-9 w-9 place-items-center rounded-full bg-[#07583f] text-xs font-bold text-white">{{ user?.name?.slice(0,2).toUpperCase() || 'AD' }}</span><div class="min-w-0"><b class="block truncate text-xs">{{ user?.name }}</b><span class="block truncate text-[10px] text-slate-500">{{ user?.email }}</span></div></div>
      </div>
    </aside>

    <div class="lg:pl-64">
      <header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-5 backdrop-blur md:px-8">
        <button class="rounded-lg p-2 text-slate-600 hover:bg-slate-100" @click="sidebarOpen=!sidebarOpen"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16" /></svg></button>
        <div class="flex items-center gap-3">
          <Link href="/" class="hidden rounded-lg px-3 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100 sm:block">Lihat Website</Link>
          <Link href="/logout" method="post" as="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:border-red-200 hover:bg-red-50 hover:text-red-600">Keluar</Link>
        </div>
      </header>

      <main class="p-5 md:p-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
          <div><p class="text-xs font-bold uppercase tracking-[.18em] text-[#f0c959]">Portal Administrator</p><h1 class="mt-1 text-2xl font-extrabold text-white md:text-3xl">Dashboard PMB</h1><p class="mt-1 text-sm text-emerald-50/75">Pantau aktivitas penerimaan mahasiswa baru dalam satu halaman.</p></div>
          <p class="text-xs text-emerald-100/60">Beranda / <b class="text-white">Dashboard</b></p>
        </div>

        <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
          <article v-for="card in cards" :key="card.label" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between"><div><p class="text-xs font-semibold text-slate-500">{{ card.label }}</p><p class="mt-2 text-3xl font-extrabold text-[#102b24]">{{ card.value }}</p></div><span :class="`stat-${card.tone}`" class="grid h-10 w-10 place-items-center rounded-xl"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path v-if="card.icon==='users'" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.87"/><path v-else-if="card.icon==='check'" d="m5 12 4 4L19 6"/><path v-else-if="card.icon==='clock'" d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20M12 6v6l4 2"/><path v-else d="M5 3h10l4 4v14H5zM14 3v5h5"/></svg></span></div>
            <p class="mt-3 text-[11px] text-slate-400">{{ card.note }}</p>
          </article>
        </section>

        <section class="mt-5 grid gap-5 xl:grid-cols-[1.35fr_.65fr]">
          <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h2 class="font-bold text-[#102b24]">Ringkasan Seleksi</h2><p class="text-xs text-slate-400">Status berkas dan hasil seleksi pendaftar</p></div><Link href="/admin/applicants" class="text-xs font-bold text-[#087154]">Lihat data →</Link></div>
            <div class="grid gap-4 p-5 sm:grid-cols-3">
              <div class="rounded-xl bg-emerald-50 p-4"><p class="text-xs text-emerald-700">Berkas Lengkap</p><b class="mt-2 block text-2xl text-emerald-900">{{ stats.complete_documents ?? 0 }}</b></div>
              <div class="rounded-xl bg-blue-50 p-4"><p class="text-xs text-blue-700">Lulus Seleksi</p><b class="mt-2 block text-2xl text-blue-900">{{ stats.passed ?? 0 }}</b></div>
              <div class="rounded-xl bg-amber-50 p-4"><p class="text-xs text-amber-700">Pendapatan</p><b class="mt-2 block text-lg text-amber-900">{{ rupiah(stats.revenue) }}</b></div>
            </div>
          </article>

          <article class="rounded-2xl bg-gradient-to-br from-[#07583f] to-[#087154] p-5 text-white shadow-lg shadow-emerald-900/15">
            <p class="text-xs font-bold uppercase tracking-widest text-emerald-200">Akses Cepat</p><h2 class="mt-2 text-lg font-bold">Kelola proses PMB</h2><p class="mt-1 text-xs leading-5 text-emerald-100">Periksa pendaftar baru dan validasi dokumen yang masuk.</p>
            <div class="mt-5 grid gap-2"><Link href="/admin/applicants" class="rounded-xl bg-white px-4 py-3 text-center text-xs font-bold text-[#07583f]">Data Pendaftar</Link><a href="/admin/reports/applicants.csv" class="rounded-xl border border-white/25 px-4 py-3 text-center text-xs font-bold text-white hover:bg-white/10">Unduh Laporan CSV</a></div>
          </article>
        </section>
      </main>
    </div>
  </div>
</template>

<style scoped>
.stat-emerald{background:#dff7ed;color:#087154}.stat-blue{background:#e6f0ff;color:#2563eb}.stat-amber{background:#fff4d7;color:#c47b05}.stat-violet{background:#f0eaff;color:#7c3aed}
</style>
