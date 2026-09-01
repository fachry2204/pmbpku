<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\SelectionStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Applicant extends Model
{
    use HasUlids,SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['lookup_secret_hash'];

    protected $appends = ['registration_status'];

    protected function casts(): array
    {
        return ['birth_date' => 'date', 'consented_at' => 'datetime', 'submitted_at' => 'datetime', 'paid_at' => 'datetime', 'payment_status' => PaymentStatus::class, 'document_status' => DocumentStatus::class, 'selection_status' => SelectionStatus::class];
    }

    protected function registrationStatus(): Attribute
    {
        return Attribute::get(function (): array {
            if ($this->selection_status === SelectionStatus::Passed) {
                return ['key' => 'selection_passed', 'label' => 'Lulus Seleksi'];
            }
            if ($this->selection_status !== SelectionStatus::NotScheduled) {
                return ['key' => 'selection_stage', 'label' => 'Tahap Seleksi'];
            }
            if ($this->document_status === DocumentStatus::Complete) {
                return ['key' => 'documents_complete', 'label' => 'Berkas Lengkap'];
            }
            if ($this->payment_status === PaymentStatus::Paid) {
                return ['key' => 'paid', 'label' => 'Sudah Bayar'];
            }

            return ['key' => 'not_paid', 'label' => 'Belum Bayar'];
        });
    }

    public function storageDirectory(): string
    {
        return 'applicants/'.preg_replace('/[^A-Za-z0-9_-]/', '_', $this->registration_number);
    }

    public function admissionPeriod()
    {
        return $this->belongsTo(AdmissionPeriod::class);
    }

    public function documents()
    {
        return $this->hasMany(ApplicantDocument::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function score()
    {
        return $this->hasOne(ApplicantScore::class);
    }
}
