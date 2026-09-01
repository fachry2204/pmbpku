<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class RequestStatusOtpRequest extends FormRequest { public function authorize():bool{return true;} public function rules():array{return ['email'=>['required','email','max:190'],'whatsapp'=>['required','string','max:30'],'channel'=>['required','in:email']];} }
