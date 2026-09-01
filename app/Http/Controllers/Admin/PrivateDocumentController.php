<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\ApplicantDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Storage};
use Symfony\Component\HttpFoundation\BinaryFileResponse;
class PrivateDocumentController extends Controller { public function __invoke(Request $request,ApplicantDocument $document):BinaryFileResponse { abort_unless(Storage::disk($document->disk)->exists($document->path),404);DB::table('audit_logs')->insert(['user_id'=>$request->user()->id,'action'=>'document.download','auditable_type'=>ApplicantDocument::class,'auditable_id'=>$document->id,'ip'=>$request->ip(),'user_agent'=>str($request->userAgent())->limit(500),'created_at'=>now()]);return response()->download(Storage::disk($document->disk)->path($document->path),basename($document->original_name),['Content-Type'=>$document->mime_type,'X-Content-Type-Options'=>'nosniff']);} }
