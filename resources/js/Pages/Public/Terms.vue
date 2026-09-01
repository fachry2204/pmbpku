<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ content: string }>();

type TermsSection = { number: string; title: string; lines: string[] };

const parsed = computed(() => {
  const lines = props.content.split(/\r?\n/).map((line) => line.trim());
  const updated = lines.find((line) => line.startsWith('Terakhir diperbarui:')) ?? '';
  const firstSection = lines.findIndex((line) => /^1\.\s+[A-Z]/.test(line));
  const intro = lines.slice(4, firstSection).filter((line) => line && !line.startsWith('Terakhir diperbarui:'));
  const sections: TermsSection[] = [];

  for (const line of lines.slice(firstSection)) {
    const heading = line.match(/^(\d+)\.\s+([A-Z][A-Z\s]+)$/);
    if (heading) {
      sections.push({ number: heading[1], title: heading[2], lines: [] });
    } else if (line && sections.length) {
      sections.at(-1)!.lines.push(line);
    }
  }

  return { updated, intro, sections };
});

const lineType = (line: string) => line.startsWith('- ') ? 'bullet' : /^\d+\.\s/.test(line) ? 'number' : 'paragraph';
</script>

<template>
  <Head title="Syarat dan Ketentuan PMB" />
  <main class="terms-page min-h-screen text-slate-700">
    <header class="border-b border-white/10 bg-[#032f25] text-white">
      <div class="mx-auto flex max-w-6xl items-center justify-between gap-5 px-5 py-5">
        <Link href="/" aria-label="Kembali ke halaman utama"><img src="/images/logo-footer-pku.png" alt="Pendidikan Kader Ulama MUI Provinsi DKI Jakarta" class="h-auto w-52 rounded-md bg-white object-contain sm:w-64" /></Link>
        <Link href="/" class="rounded-full border border-white/25 px-4 py-2 text-sm font-bold transition hover:bg-white/10">← Halaman Utama</Link>
      </div>
    </header>

    <section class="relative overflow-hidden bg-gradient-to-br from-[#032f25] via-[#07513c] to-[#0a6b4e] px-5 py-14 text-white md:py-20">
      <div class="terms-pattern absolute inset-0"></div>
      <div class="relative mx-auto max-w-4xl text-center">
        <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl md:text-5xl">Syarat dan Ketentuan</h1>
        <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-emerald-50/80 sm:text-lg">Penerimaan Mahasiswa Baru Pendidikan Kader Ulama (PKU) MUI Jakarta</p>
        <p class="mt-5 text-sm font-semibold text-[#efd276]">{{ parsed.updated }}</p>
      </div>
    </section>

    <div class="mx-auto grid max-w-6xl gap-8 px-5 py-10 lg:grid-cols-[250px_minmax(0,1fr)] lg:py-14">
      <aside class="hidden lg:block">
        <nav class="sticky top-6 rounded-2xl border border-emerald-900/10 bg-white/90 p-5 shadow-sm backdrop-blur" aria-label="Daftar isi">
          <h2 class="font-extrabold text-[#064e3b]">Daftar Isi</h2>
          <div class="mt-4 max-h-[72vh] space-y-1 overflow-y-auto pr-2 text-xs">
            <a v-for="section in parsed.sections" :key="section.number" :href="`#bagian-${section.number}`" class="flex gap-2 rounded-lg px-2 py-2 leading-5 text-slate-500 transition hover:bg-emerald-50 hover:text-[#067052]"><b class="text-[#b38b21]">{{ section.number }}.</b><span>{{ section.title }}</span></a>
          </div>
        </nav>
      </aside>

      <article class="overflow-hidden rounded-3xl border border-emerald-900/10 bg-white shadow-xl shadow-emerald-950/5">
        <div class="border-b border-emerald-900/10 bg-emerald-50/70 p-6 sm:p-8">
          <p v-for="line in parsed.intro" :key="line" class="mt-3 first:mt-0 leading-7">{{ line }}</p>
        </div>

        <section v-for="section in parsed.sections" :id="`bagian-${section.number}`" :key="section.number" class="scroll-mt-6 border-b border-slate-100 p-6 last:border-0 sm:p-8">
          <div class="flex items-start gap-4">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-[#064e3b] text-sm font-black text-white">{{ section.number }}</span>
            <h2 class="pt-1 text-xl font-extrabold leading-8 text-[#064e3b] sm:text-2xl">{{ section.title }}</h2>
          </div>
          <div class="mt-5 space-y-3 leading-7 text-slate-600">
            <div v-for="(line, index) in section.lines" :key="`${section.number}-${index}`" :class="lineType(line) === 'bullet' ? 'flex gap-3 pl-2' : lineType(line) === 'number' ? 'rounded-xl bg-slate-50 px-4 py-3' : ''">
              <span v-if="lineType(line) === 'bullet'" class="mt-2.5 h-1.5 w-1.5 shrink-0 rounded-full bg-[#c29a27]"></span>
              <p>{{ lineType(line) === 'bullet' ? line.slice(2) : line }}</p>
            </div>
          </div>
        </section>
      </article>
    </div>

    <footer class="bg-[#032f25] px-5 py-7 text-center text-sm text-emerald-50/60">© 2026 Pendidikan Kader Ulama MUI Jakarta</footer>
  </main>
</template>

<style scoped>
.terms-page{background:linear-gradient(135deg,#effaf4 0%,#f8faf5 48%,#e7f7ed 100%)}
.terms-pattern{opacity:.12;background-image:url('/images/islamic-geometric-bg.png');background-size:520px auto;mix-blend-mode:screen}
</style>
