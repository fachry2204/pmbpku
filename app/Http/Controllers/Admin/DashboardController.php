<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\{Applicant,Payment};
use Inertia\{Inertia,Response};
class DashboardController extends Controller { public function __invoke():Response{return Inertia::render('Admin/Dashboard',['stats'=>['total'=>Applicant::count(),'paid'=>Applicant::where('payment_status','paid')->count(),'unpaid'=>Applicant::where('payment_status','unpaid')->count(),'pending_documents'=>Applicant::where('document_status','pending_review')->count(),'complete_documents'=>Applicant::where('document_status','complete')->count(),'passed'=>Applicant::where('selection_status','passed')->count(),'revenue'=>Payment::where('status','paid')->sum('total_amount')]]);} }
