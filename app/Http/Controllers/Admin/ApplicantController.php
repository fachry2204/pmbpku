<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\SelectionStatus;
use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Services\RcloneStorageService;
use App\Support\IndonesianPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ApplicantController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Applicant::query();
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('registration_number', 'like', "%{$search}%")->orWhere('full_name', 'like', "%{$search}%"));
        }foreach (['payment_status', 'document_status', 'selection_status'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        return Inertia::render('Admin/Applicants/Index', ['applicants' => $query->latest()->paginate(20)->withQueryString(), 'filters' => $request->only(['search', 'payment_status', 'document_status', 'selection_status'])]);
    }

    public function show(Applicant $applicant): Response
    {
        return Inertia::render('Admin/Applicants/Show', ['applicant' => $applicant->load('documents', 'payments')]);
    }

    public function edit(Applicant $applicant): Response
    {
        return Inertia::render('Admin/Applicants/Edit', ['applicant' => $applicant]);
    }

    public function update(Request $request, Applicant $applicant): RedirectResponse
    {
        $data = $request->validate(['full_name' => ['required', 'string', 'min:3', 'max:150'], 'birth_place' => ['required', 'string', 'max:100'], 'birth_date' => ['required', 'date', 'before_or_equal:today'], 'address' => ['required', 'string', 'max:2000'], 'email' => ['required', 'email', 'max:190', Rule::unique('applicants')->where('admission_period_id', $applicant->admission_period_id)->ignore($applicant->id)], 'whatsapp' => ['required', 'string', 'max:30']]);
        $phone = IndonesianPhone::normalize($data['whatsapp']);
        if (Applicant::where('admission_period_id', $applicant->admission_period_id)->where('whatsapp_normalized', $phone)->where('id', '!=', $applicant->id)->exists()) {
            return back()->withErrors(['whatsapp' => 'Nomor WhatsApp sudah digunakan pendaftar lain.']);
        }$whatsapp = $data['whatsapp'];
        unset($data['whatsapp']);
        $applicant->update([...$data, 'email' => strtolower($data['email']), 'whatsapp_normalized' => $phone, 'whatsapp_display' => $whatsapp]);

        return redirect()->route('admin.applicants.show', $applicant)->with('success', 'Data pendaftar berhasil diperbarui.');
    }

    public function updateStatus(Request $request, Applicant $applicant): RedirectResponse
    {
        $data = $request->validate([
            'dimension' => ['required', Rule::in(['payment', 'document', 'selection'])],
            'status' => ['required', 'string', 'max:30'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['reason'] ??= null;

        $role = $request->user()->role;
        $allowedRoles = [
            'payment' => ['super_admin', 'admin_pmb', 'finance'],
            'document' => ['super_admin', 'admin_pmb', 'reviewer'],
            'selection' => ['super_admin', 'admin_pmb'],
        ];
        abort_unless(in_array($role, $allowedRoles[$data['dimension']], true), 403);

        $allowedStatuses = match ($data['dimension']) {
            'payment' => array_column(PaymentStatus::cases(), 'value'),
            'document' => array_column(DocumentStatus::cases(), 'value'),
            'selection' => array_column(SelectionStatus::cases(), 'value'),
        };
        if (! in_array($data['status'], $allowedStatuses, true)) {
            throw ValidationException::withMessages(['status' => 'Status yang dipilih tidak valid.']);
        }
        if ($data['dimension'] === 'payment' && blank($data['reason'])) {
            throw ValidationException::withMessages(['reason' => 'Alasan wajib diisi untuk perubahan pembayaran manual.']);
        }

        $attribute = $data['dimension'].'_status';
        DB::transaction(function () use ($request, $applicant, $data, $attribute): void {
            $locked = Applicant::whereKey($applicant->id)->lockForUpdate()->firstOrFail();
            $before = $locked->{$attribute}->value;
            if ($before === $data['status']) {
                return;
            }

            $changes = [$attribute => $data['status']];
            if ($data['dimension'] === 'payment') {
                $changes['paid_at'] = $data['status'] === PaymentStatus::Paid->value ? now() : null;
            }
            if ($data['dimension'] === 'selection') {
                $changes['accepted_at'] = $data['status'] === SelectionStatus::Passed->value ? now() : null;
            }
            $locked->update($changes);

            $note = $data['reason'] ?: 'Perubahan status manual melalui Data Pendaftar';
            DB::table('status_histories')->insert([
                'applicant_id' => $locked->id,
                'dimension' => $data['dimension'],
                'from_status' => $before,
                'to_status' => $data['status'],
                'note' => $note,
                'changed_by_type' => 'user',
                'changed_by_id' => $request->user()->id,
                'created_at' => now(),
            ]);
            DB::table('audit_logs')->insert([
                'user_id' => $request->user()->id,
                'action' => 'applicant.status.manual_update',
                'auditable_type' => Applicant::class,
                'auditable_id' => $locked->id,
                'before_json' => json_encode([$attribute => $before]),
                'after_json' => json_encode([$attribute => $data['status'], 'reason' => $note]),
                'ip' => $request->ip(),
                'user_agent' => str($request->userAgent())->limit(500),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Status '.$data['dimension'].' berhasil diperbarui.');
    }

    public function download(Applicant $applicant, RcloneStorageService $drive): BinaryFileResponse
    {
        abort_unless(class_exists(ZipArchive::class), 503, 'Ekstensi PHP ZIP belum aktif di server.');
        $applicant->load('documents');
        $path = tempnam(sys_get_temp_dir(), 'pmb-applicant-');
        $zip = new ZipArchive;
        abort_unless($zip->open($path, ZipArchive::OVERWRITE) === true, 500, 'Arsip tidak dapat dibuat.');
        $summary = "DATA PENDAFTAR PMB PKU\n\nNomor: {$applicant->registration_number}\nNama: {$applicant->full_name}\nEmail: {$applicant->email}\nWhatsApp: {$applicant->whatsapp_display}\nTempat/Tanggal Lahir: {$applicant->birth_place}, {$applicant->birth_date->format('d-m-Y')}\nAlamat: {$applicant->address}\nPembayaran: {$applicant->payment_status->value}\nBerkas: {$applicant->document_status->value}\nSeleksi: {$applicant->selection_status->value}\n";
        $zip->addFromString('data-pendaftar.txt', $summary);
        $temporaryFiles = [];
        foreach ($applicant->documents as $document) {
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($document->original_name));
            $archiveName = 'dokumen/'.$document->type.'-'.$document->id.'-'.$safeName;
            if ($document->disk === 'rclone') {
                $temporary = $drive->downloadToTemporaryFile($document->path);
                $temporaryFiles[] = $temporary;
                $zip->addFile($temporary, $archiveName);
            } elseif (Storage::disk($document->disk)->exists($document->path)) {
                $zip->addFile(Storage::disk($document->disk)->path($document->path), $archiveName);
            }
        }
        $zip->close();
        foreach ($temporaryFiles as $temporary) {
            @unlink($temporary);
        }

        return response()->download($path, $applicant->registration_number.'.zip')->deleteFileAfterSend(true);
    }

    public function destroy(Applicant $applicant, RcloneStorageService $drive): RedirectResponse
    {
        $applicant->load(['documents' => fn ($q) => $q->withTrashed(), 'payments']);
        foreach ($applicant->documents as $document) {
            if ($document->disk === 'rclone') {
                $drive->delete($document->path);
            } else {
                Storage::disk($document->disk)->delete($document->path);
                if ($document->type === 'photo_4x6') {
                    try {
                        $extension = $document->extension ?: pathinfo($document->path, PATHINFO_EXTENSION);
                        $filename = $document->type.'-'.$document->id.($extension ? '.'.$extension : '');
                        $drive->delete($drive->destination($applicant->registration_number, $filename));
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                }
            }
        }Storage::disk('local')->deleteDirectory($applicant->storageDirectory());
        Storage::disk('local')->deleteDirectory('applicants/'.$applicant->id);
        DB::transaction(function () use ($applicant) {
            DB::table('notification_logs')->where('applicant_id', $applicant->id)->delete();
            DB::table('audit_logs')->where('auditable_type', Applicant::class)->where('auditable_id', $applicant->id)->delete();
            DB::table('payment_webhook_events')->whereIn('provider_reference', $applicant->payments->pluck('provider_reference')->filter())->delete();
            $applicant->forceDelete();
        });

        return redirect()->route('admin.applicants.index')->with('success', 'Pendaftar dan seluruh dokumennya telah dihapus permanen.');
    }
}
