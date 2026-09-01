<script setup lang="ts">
import {Head,Link,useForm} from '@inertiajs/vue3';import {computed,onMounted,ref,watch} from 'vue';
const props=defineProps<{channels:any[],paymentError:string|null,registrationFee:number,documentUploadEnabled:boolean}>();
const step=ref(1);
const restored=ref(false);
const DRAFT_KEY='pmb-registration-draft-v1';
const form=useForm({submission_uuid:crypto.randomUUID() as string,payment_method:'',full_name:'',birth_place:'',birth_date:'',address:'',whatsapp:'',email:'',consent:false,recommendation_letter:null as File|null,diploma:null as File|null,photo_4x6:null as File|null,identity_card:null as File|null,pddikti_screenshot:null as File|null});
const docs=[['recommendation_letter','Surat rekomendasi','Surat rekomendasi atau keterangan resmi'],['diploma','Ijazah','Ijazah S1/sederajat/Pondok Pesantren'],['photo_4x6','Foto 4×6','JPG atau PNG lebih disarankan'],['identity_card','KTP','Kartu Tanda Penduduk yang jelas'],['pddikti_screenshot','Screenshot PDDIKTI','Tangkapan layar data PDDIKTI']] as const;
const dataReady=computed(()=>form.full_name.length>=3&&form.birth_place.length>=2&&!!form.birth_date&&!!form.address&&!!form.whatsapp&&!!form.email);
const docsReady=computed(()=>docs.every(([key])=>!!form[key]));
const stepLabels=computed(()=>props.documentUploadEnabled?['Data Diri','Dokumen','Pembayaran']:['Data Diri','Pembayaran']);
const totalSteps=computed(()=>stepLabels.value.length);
const paymentStep=computed(()=>props.documentUploadEnabled?3:2);
const rupiah=(value:number)=>new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(value);
const next=()=>{if(step.value===1&&dataReady.value)step.value=2;else if(props.documentUploadEnabled&&step.value===2&&docsReady.value)step.value=3;};
const fileName=(key:typeof docs[number][0])=>form[key]?.name||'Belum ada file';
const maskDate=(event:Event)=>{const input=event.target as HTMLInputElement;const digits=input.value.replace(/\D/g,'').slice(0,8);input.value=digits.length>4?`${digits.slice(0,2)}/${digits.slice(2,4)}/${digits.slice(4)}`:digits.length>2?`${digits.slice(0,2)}/${digits.slice(2)}`:digits;form.birth_date=input.value;};
const draftFields=['submission_uuid','full_name','birth_place','birth_date','address','whatsapp','email'] as const;
const saveDraft=()=>{const data=Object.fromEntries(draftFields.map(key=>[key,form[key]]));localStorage.setItem(DRAFT_KEY,JSON.stringify({data,step:step.value}));};
onMounted(()=>{try{const draft=JSON.parse(localStorage.getItem(DRAFT_KEY)||'null');if(!draft?.data)return;draftFields.forEach(key=>{if(typeof draft.data[key]==='string')form[key]=draft.data[key]});restored.value=true;step.value=draft.step>=2&&dataReady.value?2:1;}catch{localStorage.removeItem(DRAFT_KEY);}});
watch([step,...draftFields.map(key=>()=>form[key])],saveDraft);
const submit=()=>form.post('/pendaftaran',{forceFormData:true,preserveScroll:true,onSuccess:()=>localStorage.removeItem(DRAFT_KEY),onError:(errors)=>{if(errors.email||errors.whatsapp)step.value=1;}});
</script>

<template>
  <Head title="Pendaftaran PMB" />
  <main class="islamic-gradient-page min-h-screen px-4 py-10">
    <section class="mx-auto max-w-4xl">
      <div class="mb-7 text-center"><a href="/" class="inline-flex"><img src="/images/logo-footer-pku.png" alt="Pendidikan Kader Ulama MUI Provinsi DKI Jakarta" class="h-16 max-w-full rounded-md object-contain" /></a></div>

      <div class="islamic-glass-card mb-7 rounded-2xl p-5">
        <div class="relative flex justify-between"><div class="absolute left-[12%] right-[12%] top-5 h-1 bg-slate-100"><div class="h-full bg-[#d4af37] transition-all duration-500" :style="{width:`${((step-1)/(totalSteps-1))*100}%`}"></div></div><div v-for="(label,i) in stepLabels" :key="label" class="relative z-10 flex flex-1 flex-col items-center"><span class="grid h-10 w-10 place-items-center rounded-full text-sm font-black transition" :class="step>=i+1?'bg-[#064e3b] text-white ring-4 ring-emerald-100':'bg-slate-100 text-slate-400'">{{i+1}}</span><span class="mt-2 text-center text-xs font-bold sm:text-sm" :class="step>=i+1?'text-[#064e3b]':'text-slate-400'">{{label}}</span></div></div>
      </div>

      <form @submit.prevent="submit" class="islamic-glass-card rounded-[28px] p-6 md:p-10">
        <header class="mb-8 border-b pb-6"><p class="text-sm font-bold uppercase tracking-[.2em] text-[#b38b21]">Langkah {{step}} dari {{totalSteps}}</p><h1 class="mt-2 text-3xl font-extrabold text-[#064e3b]">{{step===1?'Data Diri Pendaftar':step===paymentStep?'Pembayaran':'Unggah Dokumen'}}</h1><p v-if="step < paymentStep" class="mt-2 text-slate-500">{{step===1?'Pastikan identitas dan kontak dapat dihubungi.':'Format JPG, PNG, atau PDF; maksimal 5 MB per file.'}}</p></header>

        <section v-show="step===1" class="grid gap-5 md:grid-cols-2">
          <label v-for="f in [{k:'full_name',l:'Nama lengkap',t:'text',p:'Sesuai identitas resmi'},{k:'birth_place',l:'Tempat lahir',t:'text',p:'Kota kelahiran'},{k:'birth_date',l:'Tanggal lahir',t:'text',p:'DD/MM/YYYY'},{k:'whatsapp',l:'Nomor WhatsApp',t:'tel',p:'Contoh: 081234567890'},{k:'email',l:'Alamat email',t:'email',p:'nama@email.com'}]" :key="f.k" class="block"><span class="text-sm font-bold text-slate-700">{{f.l}} <b class="text-red-600">*</b></span><input v-model="(form as any)[f.k]" :type="f.t" :placeholder="f.p" :inputmode="f.k==='birth_date'?'numeric':undefined" :maxlength="f.k==='birth_date'?10:undefined" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 focus:border-[#07805c] focus:bg-white focus:ring-[#07805c]" @input="f.k==='birth_date'&&maskDate($event)"/><small v-if="(form.errors as any)[f.k]" class="mt-2 block font-semibold text-red-700">{{(form.errors as any)[f.k]}}</small><Link v-if="(f.k==='email'||f.k==='whatsapp')&&(form.errors as any)[f.k]" href="/cek-status" class="mt-2 inline-block text-sm font-bold text-emerald-700 underline">Cek status pendaftaran →</Link></label><label class="block md:col-span-2"><span class="text-sm font-bold text-slate-700">Alamat lengkap <b class="text-red-600">*</b></span><textarea v-model="form.address" rows="4" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 focus:border-[#07805c] focus:bg-white focus:ring-[#07805c]"></textarea></label>
        </section>

        <section v-if="documentUploadEnabled" v-show="step===2" class="space-y-4">
          <div v-if="restored" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Data diri berhasil dipulihkan. Demi keamanan browser, silakan pilih ulang semua dokumen.</div>
          <label v-for="[key,label,hint] in docs" :key="key" class="group flex cursor-pointer flex-col gap-4 rounded-2xl border border-slate-200 p-5 transition hover:border-[#07805c] hover:bg-emerald-50/40 sm:flex-row sm:items-center"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-emerald-100 text-xl text-[#087154]">▣</span><span class="min-w-0 flex-1"><b class="block text-slate-800">{{label}} <span class="text-red-600">*</span></b><small class="text-slate-500">{{hint}}</small><span class="mt-1 block truncate text-xs font-semibold" :class="form[key]?'text-[#07805c]':'text-slate-400'">{{fileName(key)}}</span></span><span class="rounded-lg border border-[#087154] px-4 py-2 text-sm font-bold text-[#087154]">Pilih File</span><input type="file" accept=".jpg,.jpeg,.png,.pdf" class="sr-only" required @change="form[key]=(($event.target as HTMLInputElement).files?.[0]||null)"/></label>
        </section>

        <section v-show="step===paymentStep">
          <div v-if="paymentError" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">{{paymentError}}</div>
          <div v-if="channels.length" class="grid gap-4 sm:grid-cols-2"><label v-for="channel in channels" :key="channel.code" class="flex cursor-pointer items-center gap-4 rounded-2xl border-2 p-5 transition" :class="form.payment_method===channel.code?'border-[#07805c] bg-emerald-50':'border-slate-100 hover:border-emerald-200'"><input v-model="form.payment_method" type="radio" :value="channel.code" required class="text-[#07805c] focus:ring-[#07805c]"/><img v-if="channel.icon_url" :src="channel.icon_url" alt="" class="h-9 w-16 object-contain"/><span><b class="block text-[#064e3b]">{{channel.name}}</b><small class="text-slate-500">{{channel.group}} · {{channel.code}}</small></span></label></div>
          <div class="mt-6 rounded-xl bg-slate-50 p-5"><div class="flex justify-between text-sm"><span>Biaya pendaftaran</span><b>{{ rupiah(props.registrationFee) }}</b></div><div class="mt-3 flex justify-between border-t pt-3 text-sm"><span>Biaya layanan</span><b>Sesuai metode pembayaran</b></div><p class="mt-4 text-xs leading-5 text-slate-500">Setelah data tersimpan, Anda akan masuk ke halaman konfirmasi pembayaran.</p></div>
          <label class="mt-5 flex gap-3 rounded-xl border border-emerald-100 bg-emerald-50 p-4"><input v-model="form.consent" type="checkbox" required class="mt-1 text-[#07805c] focus:ring-[#07805c]"/><span class="text-sm leading-6">Saya menyatakan data dan dokumen benar serta telah membaca dan menyetujui <Link href="/syarat-dan-ketentuan" target="_blank" class="font-bold text-[#067052] underline decoration-emerald-300 underline-offset-2">Syarat dan Ketentuan</Link> PMB.</span></label>
        </section>

        <div v-if="form.progress" class="mt-7 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full bg-[#07805c]" :style="{width:form.progress.percentage+'%'}"></div></div>
        <footer class="mt-8 flex items-center justify-between border-t pt-6"><button v-if="step>1" type="button" @click="step--" class="rounded-xl border border-slate-300 px-6 py-3 font-bold text-slate-700">← Kembali</button><span v-else></span><button v-if="step<paymentStep" type="button" @click="next" :disabled="step===1?!dataReady:!docsReady" class="rounded-xl bg-[#064e3b] px-7 py-3 font-bold text-white disabled:cursor-not-allowed disabled:opacity-40">Lanjutkan →</button><button v-else type="submit" :disabled="form.processing||!form.payment_method||!form.consent" class="rounded-xl bg-[#d4af37] px-7 py-3 font-extrabold text-[#173b2e] disabled:cursor-not-allowed disabled:opacity-40">{{form.processing?'Menyimpan…':'Simpan & Lanjut Bayar →'}}</button></footer>
      </form>
    </section>
  </main>
</template>

