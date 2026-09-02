<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
defineProps<{ canResetPassword?: boolean; status?: string }>();
const form=useForm({username:'',password:''});
const submit=()=>form.post(route('login'),{onFinish:()=>form.reset('password')});
</script>

<template>
  <Head title="Login Admin" />
  <main class="login-pattern relative min-h-screen overflow-x-hidden bg-gradient-to-br from-[#064e3b] via-[#076344] to-[#034532] px-4 py-3 md:h-screen md:overflow-hidden md:px-8">
    <div class="relative z-10 mx-auto max-w-3xl">
      <Link href="/" class="mb-7 inline-flex items-center gap-3 font-semibold text-white/90 transition hover:text-white"><span class="text-2xl">←</span><span>Kembali ke Halaman Utama</span></Link>

      <section class="login-card grid overflow-hidden rounded-[18px] bg-white shadow-2xl shadow-black/25 md:h-[calc(100vh-48px)] md:max-h-[560px] lg:grid-cols-[.88fr_1.12fr]">
        <aside class="hidden flex-col bg-gradient-to-br from-[#f2faf6] to-[#f7f9fb] p-6 md:p-7 lg:flex lg:p-8">
          <div class="flex items-center"><img src="/images/logo-pku-mui-jakarta.png" alt="Pendidikan Kader Ulama MUI Provinsi DKI Jakarta" class="h-12 max-w-full object-contain object-left" /></div>

          <div class="mt-12"><h1 class="max-w-md text-3xl font-extrabold leading-tight text-[#102b24] md:text-4xl">Sistem Informasi<br/>PMB Pendidikan<br/>Kader Ulama</h1><p class="mt-5 max-w-md text-base leading-8 text-slate-600">Portal terintegrasi untuk pengelolaan pendaftar, dokumen, pembayaran, nilai calon, dan hasil seleksi.</p></div>

          <div class="mt-5 space-y-4">
            <div class="flex gap-5"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-emerald-100 text-xl text-[#087154]">◇</span><div><h2 class="font-extrabold text-[#102b24]">Keamanan Data Terjamin</h2><p class="mt-1 text-sm leading-6 text-slate-500">Dokumen pendaftar disimpan privat, akses dibatasi role, dan seluruh perubahan penting diaudit.</p></div></div>
            <div class="flex gap-5"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-blue-100 text-xl text-blue-600">◎</span><div><h2 class="font-extrabold text-[#102b24]">Manajemen Terpusat</h2><p class="mt-1 text-sm leading-6 text-slate-500">Kelola pembayaran, verifikasi berkas, seleksi, laporan, dan notifikasi dalam satu dashboard.</p></div></div>
          </div>

          <p class="mt-auto border-t border-slate-200 pt-8 text-sm leading-6 text-slate-400">© 2026 Pendidikan Kader Ulama.<br class="sm:hidden"/> Seluruh hak dilindungi.</p>
        </aside>

        <div class="flex items-center px-6 py-7 md:px-9 lg:px-11">
          <div class="mx-auto w-full max-w-md">
            <div><p class="text-sm font-bold uppercase tracking-[.2em] text-[#b38b21]">Portal Administrator</p><h2 class="mt-3 text-4xl font-extrabold text-[#102b24] md:text-5xl">Selamat Datang</h2><p class="mt-3 text-base text-slate-500 md:text-lg">Silakan masuk menggunakan kredensial admin Anda.</p></div>

            <div v-if="status" class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{status}}</div>

            <form class="mt-5" @submit.prevent="submit">
              <label class="block"><span class="text-sm font-extrabold text-slate-700">Username</span><div class="relative mt-2"><span class="pointer-events-none absolute inset-y-0 left-5 grid place-items-center text-slate-400">●</span><input v-model="form.username" id="username" type="text" required autofocus autocomplete="username" placeholder="Masukkan username" class="w-full rounded-2xl border-slate-200 bg-[#eef4fd] py-4 pl-12 pr-5 text-base focus:border-[#087154] focus:ring-[#087154]"/></div><p v-if="form.errors.username" class="mt-2 text-sm font-medium text-red-600">{{form.errors.username}}</p></label>

              <label class="mt-7 block"><span class="text-sm font-extrabold text-slate-700">Password</span><div class="relative mt-2"><span class="pointer-events-none absolute inset-y-0 left-5 grid place-items-center text-slate-400">●</span><input v-model="form.password" id="password" type="password" required autocomplete="current-password" placeholder="Masukkan password" class="w-full rounded-2xl border-slate-200 bg-[#eef4fd] py-4 pl-12 pr-5 text-base focus:border-[#087154] focus:ring-[#087154]"/></div><p v-if="form.errors.password" class="mt-2 text-sm font-medium text-red-600">{{form.errors.password}}</p></label>

              <button type="submit" :disabled="form.processing" class="mt-8 w-full rounded-2xl bg-[#075c3b] py-4 text-lg font-extrabold text-white shadow-xl shadow-emerald-900/20 transition hover:-translate-y-0.5 hover:bg-[#064e3b] disabled:opacity-50">{{form.processing?'Memproses…':'Masuk ke Dashboard'}}</button>
            </form>

            <p class="mt-10 border-t border-slate-100 pt-7 text-center text-sm text-slate-400">Akses sistem dilindungi dan hanya untuk pengelola terdaftar.</p>
          </div>
        </div>
      </section>
    </div>
  </main>
</template>

<style scoped>
.login-pattern::before{content:"";position:absolute;inset:0;opacity:.13;background-image:url('/images/islamic-geometric-bg.png');background-size:680px auto;pointer-events:none}
.login-card aside{padding:1.15rem!important}
.login-card aside>div:first-child{gap:.7rem!important}
.login-card aside>div:first-child>span{width:2.6rem!important;height:2.6rem!important;font-size:.85rem!important}
.login-card aside>div:first-child b{font-size:.78rem!important}
.login-card aside>div:first-child div>span{font-size:.7rem!important}
.login-card aside>div:nth-child(2){margin-top:1rem!important}
.login-card aside h1{font-size:1.35rem!important;line-height:1.1!important}
.login-card aside h1+p{margin-top:.55rem!important;font-size:.68rem!important;line-height:1.1rem!important}
.login-card aside>div:nth-child(3){margin-top:.8rem!important;gap:.6rem!important}
.login-card aside>div:nth-child(3)>div{gap:.75rem!important}
.login-card aside>div:nth-child(3)>div>span{width:2.25rem!important;height:2.25rem!important;font-size:.9rem!important}
.login-card aside h2{font-size:.72rem!important}
.login-card aside h2+p{font-size:.62rem!important;line-height:1rem!important}
.login-card aside>p{padding-top:.75rem!important;font-size:.68rem!important;line-height:1rem!important}
.login-card>div{padding:1.1rem 1.7rem!important}
.login-card>div>div{max-width:19rem!important}
.login-card>div>div>div:first-child>p:first-child{font-size:.68rem!important}
.login-card h2{margin-top:.25rem!important;font-size:1.65rem!important}
.login-card h2+p{margin-top:.3rem!important;font-size:.7rem!important}
.login-card form{margin-top:.9rem!important}
.login-card form label{font-size:.75rem!important}
.login-card form label+label{margin-top:1rem!important}
.login-card input[type=text],.login-card input[type=password]{padding-top:.55rem!important;padding-bottom:.55rem!important;padding-left:2.25rem!important;font-size:.72rem!important;border-radius:.65rem!important}
.login-card form button{margin-top:.85rem!important;padding:.6rem!important;font-size:.75rem!important;border-radius:.65rem!important}
.login-card form+p{margin-top:.9rem!important;padding-top:.65rem!important;font-size:.6rem!important}
@media(max-width:1023px){
  .login-pattern>div{display:flex;min-height:calc(100svh - 1.5rem);flex-direction:column}
  .login-pattern>div>a{margin-bottom:0!important}
  .login-card{background:transparent!important;box-shadow:none!important}
  .login-card{display:flex!important;flex:1;align-items:center;justify-content:center}
  .login-card>div{width:100%;border:1px solid rgba(255,255,255,.75);border-radius:1.5rem;background:rgba(255,255,255,.97);padding:1.5rem!important;box-shadow:0 24px 60px rgba(0,24,18,.3)}
}
</style>

