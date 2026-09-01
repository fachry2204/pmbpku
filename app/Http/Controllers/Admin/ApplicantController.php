<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Inertia\{Inertia,Response};
class ApplicantController extends Controller {
 public function index(Request $request):Response{$query=Applicant::query();if($search=$request->string('search')->trim()->toString())$query->where(fn($q)=>$q->where('registration_number','like',"%{$search}%")->orWhere('full_name','like',"%{$search}%"));foreach(['payment_status','document_status','selection_status'] as $field)if($request->filled($field))$query->where($field,$request->input($field));return Inertia::render('Admin/Applicants/Index',['applicants'=>$query->latest()->paginate(20)->withQueryString(),'filters'=>$request->only(['search','payment_status','document_status','selection_status'])]);}
 public function show(Applicant $applicant):Response{return Inertia::render('Admin/Applicants/Show',['applicant'=>$applicant->load('documents','payments')]);}
}
