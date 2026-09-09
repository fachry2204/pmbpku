<script setup lang="ts">
import {Head,Link,useForm,usePage} from '@inertiajs/vue3';import {computed,nextTick,onMounted,ref,watch} from 'vue';
const props=defineProps<{channels:any[],paymentError:string|null,registrationFee:number,documentUploadEnabled:boolean,maxTotalUploadBytes:number}>();
const step=ref(1);
const restored=ref(false);
const clientError=ref('');
const errorAlert=ref<HTMLElement|null>(null);
const isSubmitting=ref(false);
const submitStatus=ref('');
const errorModal=ref<{title:string,message:string}|null>(null);
const page=usePage();
const DRAFT_KEY='pmb-registration-draft-v1';
const form=useForm({submission_uuid:crypto.randomUUID() as string,full_name:'',birth_place:'',birth_date:'',address:'',whatsapp:'',email:'',consent:false,recommendation_letter:null as File|null,diploma:null as File|null,photo_4x6:null as File|null,identity_card:null as File|null,pddikti_screenshot:null as File|null});
const registrationError=computed(()=>((form.errors as Record<string,string>).registration||''));
const clearRegistrationError=()=>((form.clearErrors as (...fields:string[])=>void)('registration'));
const docs=[['recommendation_letter','Surat rekomendasi','Surat rekomendasi atau keterangan resmi'],['diploma','Ijazah','Ijazah S1/sederajat/Pondok Pesantren'],['photo_4x6','Foto 4×6','JPG atau PNG lebih disarankan'],['identity_card','KTP','Kartu Tanda Penduduk yang jelas'],['pddikti_screenshot','Screenshot PDDIKTI / Penyetaraan','Tangkapan layar data PDDIKTI atau dokumen penyetaraan']] as const;
const documentKeys=docs.map(([key])=>key);
const acceptedFileTypes=['image/jpeg','image/png','application/pdf'];
const dataReady=computed(()=>form.full_name.length>=3&&form.birth_place.length>=2&&!!form.birth_date&&!!form.address&&!!form.whatsapp&&!!form.email);
const docsReady=computed(()=>docs.every(([key])=>!!form[key]));
const stepLabels=computed(()=>props.documentUploadEnabled?['Data Diri','Dokumen']:['Data Diri']);
const totalSteps=computed(()=>stepLabels.value.length);
const finalStep=computed(()=>totalSteps.value);
const next=()=>{if(step.value===1&&dataReady.value&&props.documentUploadEnabled)step.value=2;};
const fileName=(key:typeof docs[number][0])=>form[key]?.name||'Belum ada file';
const totalUploadBytes=computed(()=>docs.reduce((total,[key])=>total+(form[key]?.size||0),0));
const maxTotalUploadLabel=computed(()=>`${Math.floor(props.maxTotalUploadBytes/1024/1024)} MB`);
const closeErrorModal=()=>{errorModal.value=null;};
const showError=async(message:string,title='Pendaftaran belum dapat disimpan')=>{clientError.value=message;errorModal.value={title,message};await nextTick();errorAlert.value?.scrollIntoView({behavior:'smooth',block:'center'});};
const isAcceptedFile=(file:File)=>/\.(jpe?g|png|pdf)$/i.test(file.name)&&(['','application/octet-stream'].includes(file.type)||acceptedFileTypes.includes(file.type));
const selectFile=(key:typeof docs[number][0],event:Event)=>{const input=event.target as HTMLInputElement;const file=input.files?.[0]||null;clientError.value='';form.clearErrors(key);clearRegistrationError();if(!file){form[key]=null;return;}if(!isAcceptedFile(file)){input.value='';form[key]=null;void showError(`Berkas ${file.name} tidak dapat digunakan. Unggah hanya file JPG, JPEG, PNG, atau PDF.`,'Format file tidak didukung');return;}if(file.size>10*1024*1024){input.value='';form[key]=null;void showError(`Berkas ${file.name} melebihi batas 10 MB. Perkecil ukuran file lalu pilih kembali.`,'Ukuran file terlalu besar');return;}const previous=form[key];form[key]=file;if(totalUploadBytes.value>props.maxTotalUploadBytes){form[key]=previous;input.value='';void showError(`Total seluruh dokumen melebihi batas ${maxTotalUploadLabel.value}. Kompres atau perkecil beberapa file, lalu pilih kembali.`,'Total dokumen terlalu besar');}};
const maskDate=(event:Event)=>{const input=event.target as HTMLInputElement;const digits=input.value.replace(/\D/g,'').slice(0,8);input.value=digits.length>4?`${digits.slice(0,2)}/${digits.slice(2,4)}/${digits.slice(4)}`:digits.length>2?`${digits.slice(0,2)}/${digits.slice(2)}`:digits;form.birth_date=input.value;};
const draftFields=['submission_uuid','full_name','birth_place','birth_date','address','whatsapp','email'] as const;
const saveDraft=()=>{const data=Object.fromEntries(draftFields.map(key=>[key,form[key]]));localStorage.setItem(DRAFT_KEY,JSON.stringify({data,step:step.value}));};
onMounted(()=>{try{const draft=JSON.parse(localStorage.getItem(DRAFT_KEY)||'null');if(draft?.data){draftFields.forEach(key=>{if(typeof draft.data[key]==='string')form[key]=draft.data[key]});restored.value=true;step.value=draft.step>=2&&dataReady.value?2:1;}}catch{localStorage.removeItem(DRAFT_KEY);}const parameters=new URLSearchParams(window.location.search);if(parameters.get('upload_error')==='too_large'){void showError('Ukuran total dokumen melebihi batas server. Unggah hanya JPG, JPEG, PNG, atau PDF dengan total maksimal 10 MB.','Dokumen terlalu besar');window.history.replaceState({},'',window.location.pathname);}});
watch(()=>((page.props as any).flash?.error as string|undefined),(message)=>{if(message)void showError(message);},{immediate:true});
watch([step,...draftFields.map(key=>()=>form[key])],saveDraft);
const submit=()=>{
  clientError.value='';
  submitStatus.value='';
  clearRegistrationError();
  if(totalUploadBytes.value>props.maxTotalUploadBytes){
    void showError(`Total seluruh dokumen melebihi batas server ${maxTotalUploadLabel.value}. Kompres atau perkecil dokumen sebelum mengirim.`);
    return;
  }
  isSubmitting.value=true;
  submitStatus.value='Sedang mengirim data dan dokumen. Jangan tutup halaman ini.';
  form.post('/pendaftaran',{
    forceFormData:true,
    preserveScroll:true,
    onSuccess:()=>localStorage.removeItem(DRAFT_KEY),
    onError:(errors)=>{
      if(errors.email||errors.whatsapp)step.value=1;
      if((errors as Record<string,string>).documents||documentKeys.some(key=>Boolean((errors as Record<string,string>)[key]))){
        step.value=2;
      }
      if(errors.registration){
        step.value=finalStep.value;
        void nextTick(()=>errorAlert.value?.scrollIntoView({behavior:'smooth',block:'center'}));
      }
      const messages=[...new Set(Object.values(errors).filter((message):message is string=>typeof message==='string'&&message.length>0))];
      if(messages.length>0){
        void showError(messages.join('\n'),'Pendaftaran belum berhasil disimpan');
      }
    },
    onFinish:()=>{isSubmitting.value=false;},
  });
};
</script>

<template>
  <Head title="Pendaftaran PMB" />
  <main class="islamic-gradient-page min-h-screen px-4 py-10">
    <section class="mx-auto max-w-4xl">
      <div class="mb-7 text-center"><a href="/" class="inline-flex"><img src="/images/logo-footer-pku.png" alt="Pendidikan Kader Ulama MUI Provinsi DKI Jakarta" class="h-16 max-w-full rounded-md object-contain" /></a></div>

      <div v-if="totalSteps > 1" class="islamic-glass-card mb-7 rounded-2xl p-5">
        <div class="relative flex justify-between"><div class="absolute left-[12%] right-[12%] top-5 h-1 bg-slate-100"><div class="h-full bg-[#d4af37] transition-all duration-500" :style="{width:`${((step-1)/(totalSteps-1))*100}%`}"></div></div><div v-for="(label,i) in stepLabels" :key="label" class="relative z-10 flex flex-1 flex-col items-center"><span class="grid h-10 w-10 place-items-center rounded-full text-sm font-black transition" :class="step>=i+1?'bg-[#064e3b] text-white ring-4 ring-emerald-100':'bg-slate-100 text-slate-400'">{{i+1}}</span><span class="mt-2 text-center text-xs font-bold sm:text-sm" :class="step>=i+1?'text-[#064e3b]':'text-slate-400'">{{label}}</span></div></div>
      </div>

      <form @submit.prevent="submit" class="islamic-glass-card rounded-[28px] p-6 md:p-10">
        <header class="mb-8 border-b pb-6"><p class="text-sm font-bold uppercase tracking-[.2em] text-[#b38b21]">Langkah {{step}} dari {{totalSteps}}</p><h1 class="mt-2 text-3xl font-extrabold text-[#064e3b]">{{step===1?'Data Diri Pendaftar':props.documentUploadEnabled&&step===2?'Unggah Dokumen':'Konfirmasi Pendaftaran'}}</h1><p v-if="step < finalStep" class="mt-2 text-slate-500">{{step===1?'Pastikan identitas dan kontak dapat dihubungi.':`Hanya JPG, JPEG, PNG, atau PDF. Total seluruh dokumen maksimal ${maxTotalUploadLabel}.`}}</p></header>

        <div ref="errorAlert" v-if="clientError||registrationError" role="alert" aria-live="assertive" class="mb-6 rounded-2xl border border-red-300 bg-red-50 p-4 text-red-900 shadow-sm"><div class="flex items-start gap-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-red-100 font-black text-red-700">!</span><div><p class="font-extrabold">Pendaftaran belum berhasil dikirim</p><p class="mt-1 text-sm leading-6">{{clientError||registrationError}}</p></div></div></div>

        <section v-show="step===1" class="grid gap-5 md:grid-cols-2">
          <label v-for="f in [{k:'full_name',l:'Nama lengkap',t:'text',p:'Sesuai identitas resmi'},{k:'birth_place',l:'Tempat lahir',t:'text',p:'Kota kelahiran'},{k:'birth_date',l:'Tanggal lahir',t:'text',p:'DD/MM/YYYY'},{k:'whatsapp',l:'Nomor WhatsApp',t:'tel',p:'Contoh: 081234567890'},{k:'email',l:'Alamat email',t:'email',p:'nama@email.com'}]" :key="f.k" class="block"><span class="text-sm font-bold text-slate-700">{{f.l}} <b class="text-red-600">*</b></span><input v-model="(form as any)[f.k]" :type="f.t" :placeholder="f.p" :inputmode="f.k==='birth_date'?'numeric':undefined" :maxlength="f.k==='birth_date'?10:undefined" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 focus:border-[#07805c] focus:bg-white focus:ring-[#07805c]" @input="f.k==='birth_date'&&maskDate($event)"/><small v-if="(form.errors as any)[f.k]" class="mt-2 block font-semibold text-red-700">{{(form.errors as any)[f.k]}}</small><Link v-if="(f.k==='email'||f.k==='whatsapp')&&(form.errors as any)[f.k]" href="/cek-status" class="mt-2 inline-block text-sm font-bold text-emerald-700 underline">Cek status pendaftaran →</Link></label><label class="block md:col-span-2"><span class="text-sm font-bold text-slate-700">Alamat lengkap <b class="text-red-600">*</b></span><textarea v-model="form.address" rows="4" required class="mt-2 w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 focus:border-[#07805c] focus:bg-white focus:ring-[#07805c]"></textarea></label>
        </section>

        <section v-if="documentUploadEnabled" v-show="step===2" class="space-y-4">
          <div v-if="restored" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Data diri berhasil dipulihkan. Demi keamanan browser, silakan pilih ulang semua dokumen.</div>
          <label v-for="[key,label,hint] in docs" :key="key" class="group flex cursor-pointer flex-col gap-4 rounded-2xl border p-5 transition hover:border-[#07805c] hover:bg-emerald-50/40 sm:flex-row sm:items-center" :class="form.errors[key]?'border-red-300 bg-red-50/50':'border-slate-200'"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-emerald-100 text-xl text-[#087154]">▣</span><span class="min-w-0 flex-1"><b class="block text-slate-800">{{label}} <span class="text-red-600">*</span></b><small class="text-slate-500">{{hint}}</small><span class="mt-1 block truncate text-xs font-semibold" :class="form[key]?'text-[#07805c]':'text-slate-400'">{{fileName(key)}}</span><small v-if="form.errors[key]" class="mt-1 block font-semibold text-red-700">{{form.errors[key]}}</small></span><span class="rounded-lg border border-[#087154] px-4 py-2 text-sm font-bold text-[#087154]">Pilih File</span><input type="file" accept="image/jpeg,image/png,application/pdf,.jpg,.jpeg,.png,.pdf" class="sr-only" required @change="selectFile(key,$event)"/></label>
        </section>

        <section v-show="step===finalStep">
          <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5"><h2 class="text-xl font-extrabold text-[#064e3b]">Data siap disimpan</h2><p class="mt-2 text-sm leading-6 text-slate-600">Setelah pendaftaran berhasil, Anda akan menerima nomor registrasi. Pembayaran dapat dilanjutkan dari halaman berhasil.</p></div>
          <label class="mt-5 flex gap-3 rounded-xl border border-emerald-100 bg-emerald-50 p-4"><input v-model="form.consent" type="checkbox" required class="mt-1 text-[#07805c] focus:ring-[#07805c]"/><span class="text-sm leading-6">Saya menyatakan data dan dokumen benar serta telah membaca dan menyetujui <Link href="/syarat-dan-ketentuan" target="_blank" class="font-bold text-[#067052] underline decoration-emerald-300 underline-offset-2">Syarat dan Ketentuan</Link> PMB.</span></label>
        </section>

        <div v-if="isSubmitting" aria-live="polite" class="mt-7 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900"><span class="mr-2 inline-block animate-pulse">●</span>{{submitStatus}}</div>
        <div v-if="form.progress" class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full bg-[#07805c] transition-all" :style="{width:form.progress.percentage+'%'}"></div></div>
        <footer class="mt-8 flex items-center justify-between border-t pt-6"><button v-if="step>1" type="button" @click="step--" :disabled="isSubmitting" class="rounded-xl border border-slate-300 px-6 py-3 font-bold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40">← Kembali</button><span v-else></span><button v-if="step<finalStep" type="button" @click="next" :disabled="isSubmitting||(step===1?!dataReady:!docsReady)" class="rounded-xl bg-[#064e3b] px-7 py-3 font-bold text-white disabled:cursor-not-allowed disabled:opacity-40">Lanjutkan →</button><button v-else type="submit" :disabled="isSubmitting||form.processing||!form.consent" class="rounded-xl bg-[#d4af37] px-7 py-3 font-extrabold text-[#173b2e] disabled:cursor-not-allowed disabled:opacity-40">{{isSubmitting||form.processing?'Menyimpan…':'Simpan Pendaftaran →'}}</button></footer>
      </form>
    </section>
    <Teleport to="body">
      <div v-if="errorModal" class="fixed inset-0 z-[100] grid place-items-center bg-slate-950/60 p-4" role="presentation" @click.self="closeErrorModal">
        <section role="alertdialog" aria-modal="true" aria-labelledby="registration-error-title" class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl sm:p-8">
          <div class="flex items-start gap-4"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-red-100 text-2xl font-black text-red-700">!</span><div class="min-w-0 flex-1"><h2 id="registration-error-title" class="text-xl font-extrabold text-slate-900">{{errorModal.title}}</h2><div class="mt-3 space-y-2 text-sm leading-6 text-slate-600"><p v-for="message in errorModal.message.split('\n')" :key="message">{{message}}</p></div></div></div><button type="button" class="mt-7 w-full rounded-xl bg-[#064e3b] px-5 py-3 font-bold text-white" @click="closeErrorModal">Saya Mengerti</button>
        </section>
      </div>
    </Teleport>
  </main>
</template>

