<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DocumentRevisionController extends Controller
{
    public function __invoke(Request $request, Applicant $applicant, string $type): RedirectResponse
    {
        abort_unless($request->session()->get('status_applicant_id') === $applicant->id, 403);
        $allowed = ['recommendation_letter', 'diploma', 'photo_4x6', 'identity_card', 'payment_proof', 'pddikti_screenshot'];
        abort_unless(in_array($type, $allowed, true), 404);
        $old = $applicant->documents()->where('type', $type)->latest('version')->firstOrFail();
        abort_unless($old->verification_status === 'revision_required', 422, 'Dokumen ini tidak ditandai untuk perbaikan.');
        $request->validate(['document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']]);
        $file = $request->file('document');
        DB::transaction(function () use ($applicant, $old, $file, $type) {
            $path = $file->storeAs($applicant->storageDirectory(), Str::uuid().'.'.$file->guessExtension(), 'local');
            $old->delete();
            $applicant->documents()->create(['type' => $type, 'disk' => 'local', 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'extension' => $file->guessExtension(), 'size' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()), 'verification_status' => 'pending', 'version' => $old->version + 1]);
            $applicant->update(['document_status' => 'revision_submitted']);
        });

        return back()->with('success','Dokumen perbaikan berhasil dikirim.');
    }
}
