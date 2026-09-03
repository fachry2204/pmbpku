<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const open = ref(false);
const page = usePage();
const user = computed(() => page.props.auth?.user as { name?: string; email?: string });
const current = computed(() => page.url);
const menus = [
  ['Dashboard','/admin/dashboard','M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z'],
  ['Data Pendaftar','/admin/applicants','M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8M22 21v-2a4 4 0 0 0-3-3.87'],
  ['Pembayaran','/admin/payments','M3 6h18v12H3zM3 10h18M7 15h2'],
  ['Absen Peserta','/admin/attendance','M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
  ['Nilai Calon','/admin/applicant-scores','M4 19V5M10 19V9M16 19V3M22 19H2'],
  ['Pengguna','/admin/users','M20 21a8 8 0 0 0-16 0M12 13a5 5 0 1 0 0-10 5 5 0 0 0 0 10'],
  ['Pesan Terkirim','/admin/notification-logs','M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4'],
  ['Landing Page','/admin/landing','M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20M2 12h20M12 2c3 3 3 17 0 20M12 2c-3 3-3 17 0 20'],
  ['Pengaturan','/admin/settings','M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7M12 2v3M12 19v3M4.93 4.93l2.12 2.12M16.95 16.95l2.12 2.12M2 12h3M19 12h3M4.93 19.07l2.12-2.12M16.95 7.05l2.12-2.12'],
  ['Audit Log','/admin/audit-logs','M5 3h10l4 4v14H5zM14 3v5h5M8 13h8M8 17h6'],
];
const active = (href: string) => href === '/admin/dashboard' ? current.value.startsWith(href) : current.value.startsWith(href);
</script>

<template>
  <div class="islamic-light-bg min-h-screen text-slate-800">
    <div v-if="open" class="fixed inset-0 z-30 bg-slate-950/40 lg:hidden" @click="open=false" />
    <aside :class="open?'translate-x-0':'-translate-x-full'" class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform lg:translate-x-0">
      <Link href="/admin/dashboard" class="flex h-20 items-center gap-3 border-b border-slate-100 px-6">
        <img src="/images/logo-pku-mui-jakarta.png" alt="Pendidikan Kader Ulama MUI Provinsi DKI Jakarta" class="h-11 max-w-full object-contain object-left" />
      </Link>
      <div class="flex-1 overflow-y-auto px-3 py-5">
        <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-widest text-slate-400">Admin</p>
        <nav class="space-y-1">
          <Link v-for="([label,href,path]) in menus" :key="href" :href="href" :class="active(href) ? 'bg-[#16866d] text-white shadow-md shadow-emerald-900/15' : 'text-slate-700 hover:bg-emerald-50 hover:text-[#087154]'" class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-[13px] font-semibold transition" @click="open=false">
            <svg class="h-[18px] w-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path :d="path" /></svg>{{label}}
          </Link>
        </nav>
      </div>
      <div class="border-t border-slate-100 p-4"><div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#07583f] text-xs font-bold text-white">{{user?.name?.slice(0,2).toUpperCase()||'AD'}}</span><div class="min-w-0"><b class="block truncate text-xs">{{user?.name}}</b><span class="block truncate text-[10px] text-slate-500">{{user?.email}}</span></div></div></div>
    </aside>
    <div class="lg:pl-64">
      <header class="sticky top-0 z-20 flex h-20 items-center justify-between border-b border-slate-200 bg-white/95 px-5 backdrop-blur md:px-8">
        <button class="rounded-lg p-2 text-slate-600 hover:bg-slate-100" @click="open=!open"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16" /></svg></button>
        <div class="flex items-center gap-3"><Link href="/" class="hidden rounded-lg px-3 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100 sm:block">Lihat Website</Link><Link href="/logout" method="post" as="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 hover:bg-red-50 hover:text-red-600">Keluar</Link></div>
      </header>
      <div class="admin-page"><slot /></div>
    </div>
  </div>
</template>

<style scoped>
.admin-page :deep(main.min-h-screen){min-height:calc(100vh - 5rem);background:transparent}

/* Skala tipografi admin dibuat lebih padat dan konsisten lintas halaman. */
.admin-page {
  color: #334155;
  font-size: 14px;
}
.admin-page :deep(.text-5xl) { font-size: 2rem !important; line-height: 2.35rem !important; }
.admin-page :deep(.text-4xl) { font-size: 1.75rem !important; line-height: 2.1rem !important; }
.admin-page :deep(.text-3xl) { font-size: 1.5rem !important; line-height: 1.9rem !important; }
.admin-page :deep(.text-2xl) { font-size: 1.25rem !important; line-height: 1.65rem !important; }
.admin-page :deep(.text-xl) { font-size: 1.08rem !important; line-height: 1.5rem !important; }
.admin-page :deep(.text-lg) { font-size: .95rem !important; line-height: 1.4rem !important; }
.admin-page :deep(.text-base) { font-size: .875rem !important; line-height: 1.35rem !important; }
.admin-page :deep(.text-sm) { font-size: .79rem !important; line-height: 1.25rem !important; }
.admin-page :deep(.text-xs) { font-size: .7rem !important; line-height: 1.05rem !important; }

/* Muted text tetap terbaca di permukaan putih dan slate muda. */
.admin-page :deep(.text-slate-400) { color: #64748b !important; }
.admin-page :deep(.text-slate-500) { color: #475569 !important; }
.admin-page :deep(.text-slate-600) { color: #334155 !important; }
.admin-page :deep(.text-gray-400) { color: #6b7280 !important; }
.admin-page :deep(.text-gray-500) { color: #4b5563 !important; }

.admin-page :deep(input),
.admin-page :deep(select),
.admin-page :deep(textarea),
.admin-page :deep(button) {
  font-size: .79rem;
  line-height: 1.2rem;
}
.admin-page :deep(table) { font-size: .76rem; line-height: 1.15rem; }
.admin-page :deep(th) { font-size: .7rem; letter-spacing: .01em; }
.admin-page :deep(label),
.admin-page :deep(legend) { color: #334155; }

/* Teks pembuka berada langsung di atas background hijau tua. */
.admin-page :deep(main > section > p.text-slate-500),
.admin-page :deep(main > section > p.text-slate-600),
.admin-page :deep(main > section > div:first-child > p.text-slate-500),
.admin-page :deep(main > section > div:first-child > p.text-slate-600),
.admin-page :deep(main > section > div:first-child > div > p.text-slate-500),
.admin-page :deep(main > section > div:first-child > div > p.text-slate-600) {
  color: #d7e7e1 !important;
}

@media (max-width: 640px) {
  .admin-page :deep(.text-5xl),
  .admin-page :deep(.text-4xl) { font-size: 1.65rem !important; line-height: 2rem !important; }
  .admin-page :deep(.text-3xl) { font-size: 1.35rem !important; line-height: 1.7rem !important; }
}
</style>
