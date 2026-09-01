<?php
namespace App\Http\Controllers\Public;
use App\Actions\QueueApplicantNotification;
use App\Enums\{DocumentStatus,PaymentStatus,SelectionStatus};
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicantRequest;
use App\Models\{AdmissionPeriod,Applicant};
use App\Support\IndonesianPhone;
use App\Services\Duitku\DuitkuClient;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\{Cache,DB,Hash};
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
class RegistrationController extends Controller {
 public function create(DuitkuClient $duitku, SettingsService $settings):Response{$channels=[];$paymentError=null;$amount=(int)$settings->get('pmb.registration_fee',250000);try{$channels=Cache::remember('duitku.channels.'.config('services.duitku.mode').'.'.$amount,300,fn()=>$duitku->channels($amount));}catch(\Throwable){$paymentError='Metode pembayaran belum tersedia. Silakan hubungi panitia.';}return Inertia::render('Public/Register',['channels'=>$channels,'paymentError'=>$paymentError,'registrationFee'=>$amount]);}
 public function store(StoreApplicantRequest $request,QueueApplicantNotification $notifications):RedirectResponse {
  $data=$request->validated(); $phone=IndonesianPhone::normalize($data['whatsapp']);
  $applicant=DB::transaction(function()use($request,$data,$phone){
   $period=AdmissionPeriod::where('is_active',true)->lockForUpdate()->firstOrFail();
   if($existing=Applicant::where('admission_period_id',$period->id)->where('submission_uuid',$data['submission_uuid'])->first()) return $existing;
   $sequence=Applicant::where('admission_period_id',$period->id)->withTrashed()->count()+1;
   $applicant=Applicant::create(['admission_period_id'=>$period->id,'registration_number'=>sprintf('%s-%d-%06d',$period->registration_prefix,$period->year,$sequence),'submission_uuid'=>$data['submission_uuid'],'full_name'=>$data['full_name'],'birth_place'=>$data['birth_place'],'birth_date'=>$data['birth_date'],'address'=>$data['address'],'whatsapp_normalized'=>$phone,'whatsapp_display'=>$data['whatsapp'],'email'=>strtolower($data['email']),'payment_status'=>PaymentStatus::Unpaid,'document_status'=>DocumentStatus::PendingReview,'selection_status'=>SelectionStatus::NotScheduled,'consented_at'=>now(),'submitted_at'=>now(),'lookup_secret_hash'=>Hash::make(Str::random(48))]);
   foreach(['recommendation_letter','diploma','photo_4x6','identity_card','pddikti_screenshot'] as $type){$file=$request->file($type);$path=$file->storeAs('applicants/'.$applicant->id,Str::uuid().'.'.$file->guessExtension(),'local');$applicant->documents()->create(['type'=>$type,'disk'=>'local','path'=>$path,'original_name'=>$file->getClientOriginalName(),'mime_type'=>$file->getMimeType(),'extension'=>$file->guessExtension(),'size'=>$file->getSize(),'sha256'=>hash_file('sha256',$file->getRealPath())]);}
   return $applicant;
  });
  $notifications->execute($applicant,'registration_created',"Pendaftaran {$applicant->registration_number} berhasil diterima. Simpan nomor pendaftaran Anda.");
  return redirect()->route('payment.show',['registrationNumber'=>$applicant->registration_number,'method'=>$data['payment_method'],'registered'=>1]);
 }
 public function success(string $registrationNumber):Response{$applicant=Applicant::where('registration_number',$registrationNumber)->firstOrFail();return Inertia::render('Public/Success',['applicant'=>['registration_number'=>$applicant->registration_number,'full_name'=>$applicant->full_name,'payment_status'=>$applicant->payment_status]]);}
}
