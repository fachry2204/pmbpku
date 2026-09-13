<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps<{ payments: any; filters: { status?: string; provider?: string }; statusSummary: Record<string, number> }>();
const filters = reactive({ ...props.filters, status: props.filters?.status || '' });
const cards = computed(() => [
  { key: '', label: 'Semua Pendaftar', count: props.statusSummary.all ?? 0, description: 'Seluruh pendaftar dengan transaksi', tone: 'slate', icon: 'M4 19v-1a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v1M8 7a4 4 0 1 0 8 0 4 4 0 0 0-8 0' },
  { key: 'paid', label: 'Sudah Bayar', count: props.statusSummary.paid ?? 0, description: 'Pembayaran telah diterima', tone: 'emerald', icon: 'm5 12 4 4L19 6' },
  { key: 'unpaid', label: 'Belum Bayar', count: props.statusSummary.unpaid ?? 0, description: 'Menunggu pembayaran', tone: 'rose', icon: 'M3 7h18v12H3zM3 10h18M7 15h3' },
]);
const selectStatus = (status: string) => { filters.status = status; router.get('/admin/payments', filters, { preserveState: true, preserveScroll: true, replace: true }); };
const paymentStatus = (payment: any) => payment.applicant?.payment_status || payment.status;
const paymentLabel = (status: string) => ({ paid: 'Lunas', unpaid: 'Belum Bayar', pending: 'Menunggu Pembayaran', failed: 'Gagal', expired: 'Kedaluwarsa', refunded: 'Dikembalikan' } as Record<string, string>)[status] || status;
const statusClass = (status: string) => status === 'paid' ? 'bg-emerald-100 text-emerald-800 ring-emerald-200' : ['failed', 'expired'].includes(status) ? 'bg-rose-100 text-rose-700 ring-rose-200' : 'bg-amber-100 text-amber-800 ring-amber-200';
const formatMoney = (value: number | string | null | undefined) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0));
const verify = (payment: any, decision: string) => { const note = decision === 'reject' ? window.prompt('Alasan penolakan:') || '' : null; if (decision === 'reject' && !note) return; router.patch(`/admin/payments/${payment.id}/verify`, { decision, note }, { preserveScroll: true }); };
</script>

<template>
  <Head title="Pembayaran" />
  <main class="payment-page min-h-screen px-4 py-6 sm:px-6 lg:px-8 lg:py-9">
    <section class="relative z-10 mx-auto max-w-[1380px]">
      <Link href="/admin/dashboard" class="inline-flex items-center gap-2 text-sm font-bold text-white/90 transition hover:text-white">← Dashboard</Link>
      <div class="mt-1 flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-3xl font-black tracking-tight text-white">Pembayaran</h1><p class="mt-1 text-sm text-emerald-100">Pantau pembayaran berdasarkan status terbaru pendaftar.</p></div><button v-if="filters.status" type="button" class="rounded-xl border border-white/30 bg-white/10 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/20" @click="selectStatus('')">Tampilkan semua</button></div>
      <section class="mt-6 grid gap-4 sm:grid-cols-3" aria-label="Ringkasan pembayaran">
        <button v-for="card in cards" :key="card.key || 'all'" type="button" class="payment-card text-left" :class="[filters.status === card.key ? `payment-card-${card.tone}-active` : 'payment-card-idle']" :aria-pressed="filters.status === card.key" @click="selectStatus(card.key)"><span class="card-icon" :class="`card-icon-${card.tone}`"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path :d="card.icon" /></svg></span><div class="min-w-0"><p class="text-2xl font-black">{{ card.count }}</p><p class="mt-1 text-sm font-extrabold">{{ card.label }}</p><p class="mt-1 text-xs" :class="filters.status === card.key ? 'text-white/75' : 'text-slate-500'">{{ card.description }}</p></div></button>
      </section>
      <section class="mt-5 overflow-hidden rounded-2xl border border-white/70 bg-white shadow-2xl shadow-black/20">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4"><div><h2 class="font-black text-emerald-950">Daftar Pembayaran</h2><p class="mt-0.5 text-xs text-slate-500">Status mengikuti status pembayaran pada data pendaftar.</p></div><p class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">{{ payments.total }} transaksi</p></div>
        <div class="overflow-x-auto"><table class="w-full min-w-[850px] text-left text-sm"><thead class="bg-emerald-950 text-white"><tr><th class="p-4">Pendaftar</th><th class="p-4">Provider</th><th class="p-4">Nominal</th><th class="p-4">Status Pendaftar</th><th class="p-4 text-right">Aksi Manual</th></tr></thead><tbody>
          <tr v-for="payment in payments.data" :key="payment.id" class="border-b border-slate-100 transition hover:bg-emerald-50/50"><td class="p-4"><Link :href="`/admin/applicants/${payment.applicant.id}`" class="font-bold text-emerald-800 hover:underline">{{ payment.applicant.registration_number }}</Link><p class="mt-1 text-xs font-semibold text-slate-600">{{ payment.applicant.full_name }}</p></td><td class="p-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ payment.provider }}</span></td><td class="p-4 font-bold text-slate-800">{{ formatMoney(payment.total_amount) }}</td><td class="p-4"><span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold ring-1 ring-inset" :class="statusClass(paymentStatus(payment))">{{ paymentLabel(paymentStatus(payment)) }}</span></td><td class="p-4 text-right"><span v-if="payment.provider === 'manual' && payment.status === 'pending' && paymentStatus(payment) !== 'paid'" class="inline-flex gap-2"><button type="button" class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-600" @click="verify(payment, 'accept')">Terima</button><button type="button" class="rounded-lg bg-rose-100 px-3 py-1.5 text-xs font-bold text-rose-800 hover:bg-rose-200" @click="verify(payment, 'reject')">Tolak</button></span><span v-else class="text-xs text-slate-400">—</span></td></tr>
          <tr v-if="!payments.data.length"><td colspan="5" class="p-12 text-center text-sm text-slate-500">Tidak ada data pembayaran pada status ini.</td></tr>
        </tbody></table></div>
      </section>
    </section>
  </main>
</template>

<style scoped>
.payment-page{position:relative;isolation:isolate;background:linear-gradient(135deg,#022c22,#065f46 56%,#01372c)}.payment-page:before{content:'';position:fixed;inset:0;z-index:-1;background-image:url('/images/islamic-geometric-bg.png');background-size:420px;opacity:.09}.payment-card{display:flex;min-height:130px;align-items:flex-start;gap:1rem;border:1px solid;padding:1.25rem;border-radius:1rem;box-shadow:0 10px 25px #022c2224;transition:.2s}.payment-card:hover{transform:translateY(-2px)}.payment-card-idle{border-color:#e2e8f0;background:#fff;color:#0f172a}.payment-card-slate-active{border-color:#334155;background:#334155;color:#fff}.payment-card-emerald-active{border-color:#047857;background:#047857;color:#fff}.payment-card-rose-active{border-color:#e11d48;background:#e11d48;color:#fff}.card-icon{display:grid;height:2.75rem;width:2.75rem;flex:none;place-items:center;border-radius:.8rem}.card-icon svg{height:1.35rem;width:1.35rem}.card-icon-slate{background:#f1f5f9;color:#334155}.card-icon-emerald{background:#d1fae5;color:#047857}.card-icon-rose{background:#ffe4e6;color:#be123c}.payment-card-slate-active .card-icon,.payment-card-emerald-active .card-icon,.payment-card-rose-active .card-icon{background:#ffffff2e;color:#fff}
</style>
