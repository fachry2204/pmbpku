<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
class ReportController extends Controller { public function applicants(Request $request):StreamedResponse { $query=Applicant::query();foreach(['payment_status','document_status','selection_status','admission_period_id'] as $field)if($request->filled($field))$query->where($field,$request->input($field));return response()->streamDownload(function()use($query){$out=fopen('php://output','w');fputcsv($out,['Nomor Pendaftaran','Nama','Email','WhatsApp','Pembayaran','Berkas','Seleksi','Tanggal Daftar']);$query->orderBy('registration_number')->chunk(500,function($rows)use($out){foreach($rows as $a)fputcsv($out,[$a->registration_number,$a->full_name,$a->email,$a->whatsapp_normalized,$a->payment_status->value,$a->document_status->value,$a->selection_status->value,$a->submitted_at]);});fclose($out);},'laporan-pendaftar-'.now()->format('Ymd-His').'.csv',['Content-Type'=>'text/csv; charset=UTF-8']);} }
