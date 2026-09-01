<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreApplicantRequest extends FormRequest {
 public function authorize():bool{return true;}
 public function rules():array{return ['submission_uuid'=>['required','uuid'],'payment_method'=>['required','string','max:30'],'full_name'=>['required','string','min:3','max:150'],'birth_place'=>['required','string','min:2','max:100'],'birth_date'=>['required','date','before_or_equal:today'],'address'=>['required','string','max:2000'],'whatsapp'=>['required','string','max:30'],'email'=>['required','email:rfc','max:190'],'consent'=>['accepted'],...collect(['recommendation_letter','diploma','photo_4x6','identity_card','pddikti_screenshot'])->mapWithKeys(fn($key)=>[$key=>['required','file','mimes:jpg,jpeg,png,pdf','max:5120']])->all()];}
 public function messages():array{return ['required'=>'Kolom :attribute wajib diisi.','accepted'=>'Persetujuan wajib diberikan.','mimes'=>'Berkas :attribute harus JPG, PNG, atau PDF.','max'=>'Nilai :attribute melebihi batas yang diizinkan.'];}
}
