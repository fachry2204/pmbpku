<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';
type Channel = { code: string; name: string; group?: string; icon_url?: string | null };
const props = defineProps<{ applicant: any; registrationFee: number; channels: Channel[]; error: string | null; selectedMethod?: string; registered?: boolean }>();
const form = useForm({ method: props.selectedMethod || '' });
const selectedChannel = computed(() => props.channels.find(channel => channel.code === form.method));
const rupiah = (value: number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
const startGatewayPayment = () => form.post(`/pembayaran/${props.applicant.registration_number}/gateway`, { preserveScroll: true });
onMounted(() => {
  if (!props.registered || !form.method || props.error) return;

  // A rejected gateway request redirects back to this same Inertia page. Keep
  // the automatic hand-off to one attempt so it cannot remount and submit in
  // a loop until Laravel responds with HTTP 429. The button remains available
  // for a deliberate retry after the error is shown.
  const attemptKey = `pmb-payment-auto:${props.applicant.registration_number}:${form.method}`;
  try {
    if (sessionStorage.getItem(attemptKey)) return;
    sessionStorage.setItem(attemptKey, '1');
  } catch {
    // A disabled sessionStorage must not prevent checkout.
  }
  startGatewayPayment();
});
</script>

<template>
  <Head title="Pembayaran" />
  <main class="min-h-screen bg-emerald-50 p-5">
    <section class="mx-auto max-w-3xl space-y-7 rounded-3xl bg-white p-8 shadow-xl">
      <div><p class="font-semibold text-emerald-700">{{ applicant.registration_number }}</p><h1 class="text-3xl font-bold text-emerald-950">Lanjutkan pembayaran</h1><p class="mt-2 text-slate-600">{{ applicant.full_name }} · status {{ applicant.payment_status }}</p></div>
      <div>
        <h2 class="text-xl font-bold">Pembayaran otomatis</h2>
        <div v-if="error" class="mt-3 rounded-xl bg-amber-50 p-4 text-amber-900">{{ error }}</div>
        <form v-else class="mt-3 space-y-4" @submit.prevent="startGatewayPayment">
          <div v-if="registered && form.processing" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">Menghubungkan ke halaman pembayaran. Mohon tunggu dan jangan tutup halaman ini…</div>
          <div v-if="form.errors.method" role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-800">{{ form.errors.method }}</div>
          <div v-if="selectedMethod" class="rounded-2xl border-2 border-emerald-700 bg-emerald-50 p-5">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Metode yang dipilih</p>
            <div class="mt-3 flex items-center gap-4"><img v-if="selectedChannel?.icon_url" :src="selectedChannel.icon_url" alt="" class="h-10 w-20 object-contain" /><div><b class="block text-lg text-emerald-950">{{ selectedChannel?.name || selectedMethod }}</b><small class="text-slate-500">{{ selectedChannel?.group }}</small></div></div>
            <div class="mt-4 flex justify-between border-t border-emerald-200 pt-4 text-sm"><span>Biaya pendaftaran</span><b>{{ rupiah(registrationFee) }}</b></div>
          </div>
          <template v-else>
            <p class="text-sm text-slate-600">Pilih metode pembayaran untuk melanjutkan.</p>
            <label v-for="channel in channels" :key="channel.code" class="flex cursor-pointer items-center gap-4 rounded-xl border p-4 hover:border-emerald-700"><input v-model="form.method" type="radio" :value="channel.code" required /><img v-if="channel.icon_url" :src="channel.icon_url" alt="" class="h-8 w-16 object-contain" /><span><b>{{ channel.name }}</b><small class="block text-slate-500">{{ channel.group }}</small></span></label>
          </template>
          <button :disabled="form.processing || !form.method" class="w-full rounded-xl bg-emerald-800 py-3 font-bold text-white disabled:opacity-50">{{ form.processing ? 'Memproses…' : 'Bayar Sekarang →' }}</button>
        </form>
      </div>
      <div class="border-t pt-6">
        <p class="text-center text-sm text-slate-600">Sudah melakukan pembayaran atau ingin memantau proses pendaftaran?</p>
        <Link :href="`/cek-status?identifier=${encodeURIComponent(applicant.registration_number)}`" class="mt-3 flex w-full items-center justify-center rounded-xl border border-emerald-800 py-3 font-bold text-emerald-900 transition hover:bg-emerald-50">← Kembali ke Cek Status Pendaftaran</Link>
      </div>
    </section>
  </main>
</template>
