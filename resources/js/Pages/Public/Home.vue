<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
defineProps<{ content: any; registrationYear: number }>();
const services=[
  {no:'01',title:'Pendaftaran Online',text:'Isi formulir dan unggah dokumen persyaratan secara aman.',href:'/pendaftaran'},
  {no:'02',title:'Cek Status',text:'Pantau pembayaran, verifikasi berkas, dan hasil seleksi.',href:'/cek-status'},
  {no:'03',title:'Pembayaran Terintegrasi',text:'Pembayaran otomatis melalui payment gateway dalam satu alur.',href:'/pendaftaran'},
  {no:'04',title:'Informasi Seleksi',text:'Jadwal, lokasi, dan hasil tes tersedia dalam satu portal.',href:'/cek-status'},
];
</script>

<template>
  <Head title="PMB Pendidikan Kader Ulama" />
  <div class="islamic-gradient-page min-h-screen font-sans text-slate-800">
    <div class="bg-[#054b36] text-white/85">
      <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-2 px-5 py-2 text-xs md:text-sm">
        <div class="flex gap-6"><span>◉ Pendidikan Kader Ulama</span><span class="hidden sm:inline">✦ Membina Ulama, Menguatkan Umat</span></div>
        <div class="flex gap-5"><span>Periode PMB {{ registrationYear }}</span><a href="#kontak" class="font-semibold text-[#e6bf55]">Hubungi Panitia</a></div>
      </div>
    </div>

    <header class="sticky top-0 z-40 border-b border-black/5 bg-white/95 backdrop-blur">
      <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4">
        <Link href="/" class="flex items-center gap-3">
          <img src="/images/logo-pku-mui-jakarta.png" alt="Pendidikan Kader Ulama MUI Provinsi DKI Jakarta" class="h-12 w-auto object-contain sm:h-14" />
        </Link>
        <nav class="hidden items-center gap-8 text-sm font-semibold text-[#164a3a] lg:flex">
          <a href="#layanan">Layanan</a><Link href="/pendaftaran">Pendaftaran</Link><Link href="/cek-status">Cek Status</Link><a href="#kontak">Kontak</a>
        </nav>
        <div class="flex items-center gap-3"><Link href="/cek-status" class="rounded-full border border-[#0a674b] px-5 py-2.5 text-sm font-bold text-[#07543e]">Cek Status</Link></div>
      </div>
    </header>

    <main>
      <section class="islamic-pattern relative overflow-hidden bg-gradient-to-br from-[#021f18] via-[#04382a] to-[#07513c] text-white">
        <div class="hero-islamic-overlay absolute inset-0"></div>
        <div class="relative mx-auto grid min-h-[620px] max-w-7xl items-center gap-12 px-5 py-20 lg:grid-cols-[1.18fr_.82fr]">
          <div>
            <span class="inline-flex rounded-full border border-emerald-300/20 bg-emerald-400/10 px-5 py-2 text-sm font-bold text-emerald-100">Penerimaan Mahasiswa Baru • {{ registrationYear }}</span>
            <h1 class="mt-7 max-w-3xl text-3xl font-extrabold leading-[1.12] tracking-tight sm:text-4xl lg:text-5xl">{{ content.hero_title }}</h1>
            <div class="mt-5 h-1.5 w-28 rounded-full bg-[#e2b93f]"></div>
            <p class="mt-7 max-w-2xl text-lg leading-8 text-emerald-50/85 md:text-xl">{{ content.hero_description }}</p>
            <div class="mt-9 flex flex-wrap gap-4"><Link href="/pendaftaran" class="rounded-xl bg-[#e2b93f] px-7 py-4 font-extrabold text-[#143d30] shadow-xl shadow-black/10 transition hover:-translate-y-0.5">Daftar Sekarang →</Link><Link href="/cek-status" class="rounded-xl border border-white/35 bg-white/10 px-7 py-4 font-bold text-white backdrop-blur transition hover:bg-white/20">Cek Status Pendaftaran</Link></div>
          </div>

          <aside class="relative hidden min-h-[430px] items-end justify-center lg:flex">
            <img src="/images/tokoh-pku.png" alt="Tokoh Pendidikan Kader Ulama MUI Provinsi DKI Jakarta" class="relative z-10 w-[125%] max-w-[760px] cursor-pointer object-contain object-bottom drop-shadow-[0_24px_28px_rgba(0,0,0,.3)] transition duration-500 ease-out hover:-translate-y-3 hover:scale-110 hover:drop-shadow-[0_34px_34px_rgba(0,0,0,.38)] motion-reduce:transform-none motion-reduce:transition-none" />
          </aside>
        </div>
      </section>

      <section id="layanan" class="relative z-10 mx-auto -mt-12 max-w-7xl px-5">
        <div class="grid overflow-hidden rounded-2xl bg-white shadow-2xl shadow-emerald-950/10 md:grid-cols-2 lg:grid-cols-4">
          <Link v-for="item in services" :key="item.no" :href="item.href" class="group border-b border-slate-100 p-7 transition hover:bg-[#f0f8f4] md:border-r lg:border-b-0">
            <span class="text-xs font-black tracking-widest text-[#c29a27]">{{ item.no }}</span><h3 class="mt-3 text-lg font-extrabold text-[#064e3b] group-hover:text-[#07805c]">{{ item.title }}</h3><p class="mt-2 text-sm leading-6 text-slate-500">{{ item.text }}</p><span class="mt-4 inline-block text-[#07805c]">Selengkapnya →</span>
          </Link>
        </div>
      </section>

    </main>

    <footer id="kontak" class="bg-[#032f25] text-white">
      <div class="mx-auto grid max-w-7xl gap-10 px-5 py-16 md:grid-cols-3">
        <div><img src="/images/logo-footer-pku.png" alt="Pendidikan Kader Ulama MUI Provinsi DKI Jakarta" class="h-auto w-full max-w-[280px] object-contain"/><p class="mt-5 leading-7 text-emerald-50/65">Portal resmi penerimaan mahasiswa baru Pendidikan Kader Ulama.</p></div>
        <div><h3 class="font-bold text-[#e4bd4a]">Tautan Cepat</h3><div class="mt-4 grid gap-3 text-sm text-emerald-50/70"><Link href="/pendaftaran">Pendaftaran</Link><Link href="/cek-status">Cek Status</Link><Link href="/syarat-dan-ketentuan">Syarat dan Ketentuan</Link></div></div>
        <div>
          <h3 class="font-bold text-[#e4bd4a]">Hubungi Panitia</h3>
          <p class="mt-4 font-extrabold uppercase tracking-wide text-white">MUI Provinsi DKI Jakarta</p>
          <div class="mt-5 space-y-4 text-sm leading-6 text-emerald-50/75">
            <div class="flex items-start gap-3"><svg class="mt-0.5 h-5 w-5 shrink-0 text-[#e4bd4a]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg><span>Jalan Kramat Jaya Raya, Kelurahan Tugu Utara, Kecamatan Koja, Jakarta Utara, Kode Pos 14260</span></div>
            <a href="mailto:sekretariat@muijakarta.or.id" class="flex items-center gap-3 transition hover:text-white"><svg class="h-5 w-5 shrink-0 text-[#e4bd4a]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg><span>sekretariat@muijakarta.or.id</span></a>
            <a href="tel:+628988000739" class="flex items-center gap-3 transition hover:text-white"><svg class="h-5 w-5 shrink-0 text-[#e4bd4a]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.68 2.8a2 2 0 0 1-.45 2.11L8.07 9.9a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.32 1.84.55 2.8.68A2 2 0 0 1 22 16.92Z"/></svg><span>+62 898-8000-739</span></a>
          </div>
        </div>
      </div>
      <div class="border-t border-white/10 px-5 py-5 text-center text-xs text-emerald-50/50">© {{ registrationYear }} Pendidikan Kader Ulama. Seluruh hak dilindungi.</div>
    </footer>
  </div>
</template>

<style scoped>
.islamic-pattern::before{content:"";position:absolute;inset:0;z-index:1;opacity:.2;background-image:url('/images/islamic-geometric-bg.png');background-size:600px auto;mix-blend-mode:screen;pointer-events:none}
.islamic-pattern::after{content:"";position:absolute;z-index:1;right:-12rem;top:-14rem;width:40rem;height:40rem;border:2px solid rgba(230,191,85,.28);outline:1px solid rgba(255,255,255,.12);border-radius:42% 58% 44% 56%;transform:rotate(35deg);box-shadow:0 0 0 5rem rgba(4,78,59,.08),0 0 0 5.15rem rgba(230,191,85,.08);pointer-events:none}
.hero-islamic-overlay{z-index:2;background:radial-gradient(circle at 82% 18%,rgba(212,175,55,.14),transparent 31%),radial-gradient(circle at 14% 92%,rgba(16,185,129,.09),transparent 35%),linear-gradient(90deg,rgba(1,24,18,.48),rgba(2,48,35,.2) 48%,rgba(3,43,32,.36));pointer-events:none}
.islamic-pattern>.relative{z-index:3}
</style>

