<?php

namespace App\Http\Controllers\Public;

use App\Models\{AdmissionPeriod, Applicant};
use App\Support\IndonesianPhone;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Validation\ValidationException;
use Inertia\{Inertia, Response};

class StatusLookupController
{
    public function index(): Response
    {
        return Inertia::render('Public/StatusLookup');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
        ], [
            'identifier.required' => 'Masukkan email atau nomor HP/WhatsApp terdaftar.',
        ]);

        $identifier = trim($data['identifier']);
        $period = AdmissionPeriod::where('is_active', true)->first();
        $applicant = null;

        if ($period) {
            $query = Applicant::where('admission_period_id', $period->id);
            if (str_contains($identifier, '@')) {
                $applicant = $query->where('email', strtolower($identifier))->first();
            } else {
                try {
                    $phone = IndonesianPhone::normalize($identifier);
                    $applicant = $query->where('whatsapp_normalized', $phone)->first();
                } catch (\InvalidArgumentException) {
                    $applicant = null;
                }
            }
        }

        if (! $applicant) {
            throw ValidationException::withMessages([
                'identifier' => 'Email atau nomor HP tidak ditemukan pada periode pendaftaran aktif.',
            ]);
        }

        $request->session()->put('status_applicant_id', $applicant->id);

        return redirect()->route('status.show');
    }

    public function show(Request $request): Response
    {
        $id = $request->session()->get('status_applicant_id');
        abort_unless($id, 403);
        $applicant = Applicant::with([
            'documents:id,applicant_id,type,verification_status,review_note',
            'payments:id,applicant_id,status,checkout_url,expires_at',
        ])->findOrFail($id);

        return Inertia::render('Public/StatusDetail', ['applicant' => [
            'id' => $applicant->id,
            'registration_number' => $applicant->registration_number,
            'full_name' => $applicant->full_name,
            'payment_status' => $applicant->payment_status,
            'document_status' => $applicant->document_status,
            'selection_status' => $applicant->selection_status,
            'documents' => $applicant->documents,
            'payments' => $applicant->payments,
        ]]);
    }
}
