<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Applicant,ApplicantDocument};
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
class DocumentReviewController extends Controller {
 public function update(Request $request,Applicant $applicant,ApplicantDocument $document):RedirectResponse { abort_unless($document->applicant_id===$applicant->id,404);$data=$request->validate(['verification_status'=>['required','in:valid,revision_required'],'review_note'=>['nullable','string','max:1000','required_if:verification_status,revision_required']]);DB::transaction(function()use($request,$applicant,$document,$data){$before=$document->verification_status;$document->update([...$data,'reviewed_by'=>$request->user()->id,'reviewed_at'=>now()]);DB::table('document_reviews')->insert(['applicant_document_id'=>$document->id,'reviewer_id'=>$request->user()->id,'before_status'=>$before,'after_status'=>$data['verification_status'],'note'=>$data['review_note']??null,'created_at'=>now(),'updated_at'=>now()]);$statuses=$applicant->documents()->pluck('verification_status');$new=$statuses->contains('revision_required')?'incomplete':($statuses->every(fn($s)=>$s==='valid')?'complete':'pending_review');if($applicant->document_status->value!==$new){DB::table('status_histories')->insert(['applicant_id'=>$applicant->id,'dimension'=>'document','from_status'=>$applicant->document_status->value,'to_status'=>$new,'note'=>'Hasil pemeriksaan dokumen','changed_by_type'=>'user','changed_by_id'=>$request->user()->id,'created_at'=>now()]);$applicant->update(['document_status'=>$new]);}});return back()->with('success','Status dokumen diperbarui.'); }
}
