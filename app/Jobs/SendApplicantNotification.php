<?php
namespace App\Jobs;
use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue,SerializesModels};
use Illuminate\Support\Facades\{Http,Mail};
use Throwable;
class SendApplicantNotification implements ShouldQueue { use Dispatchable,InteractsWithQueue,Queueable,SerializesModels; public int $tries=3; public int $timeout=30; public function __construct(public int $logId,public string $message){} public function handle():void{$log=NotificationLog::with('applicant')->findOrFail($this->logId);if($log->status==='sent')return;$log->increment('attempts');try{if($log->channel==='email')Mail::raw($this->message,fn($mail)=>$mail->to($log->applicant->email)->subject('Informasi PMB PKU'));else{$token=(string)config('services.fonnte.token');if($token===''){$log->update(['status'=>'skipped','last_error'=>'Fonnte belum dikonfigurasi.']);return;}$response=Http::withHeaders(['Authorization'=>$token])->asForm()->timeout(15)->post(rtrim(config('services.fonnte.base_url'),'/').'/send',['target'=>$log->applicant->whatsapp_normalized,'message'=>$this->message,'countryCode'=>config('services.fonnte.country_code','62')])->throw();$log->provider_request_id=(string)($response->json('requestid')??'');}$log->update(['status'=>'sent','sent_at'=>now(),'last_error'=>null]);}catch(Throwable $e){$log->update(['status'=>'failed','last_error'=>str($e->getMessage())->limit(500)]);throw $e;}} public function backoff():array{return [30,120,300];}}
