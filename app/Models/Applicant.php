<?php
namespace App\Models;
use App\Enums\{PaymentStatus,DocumentStatus,SelectionStatus};
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Applicant extends Model { use HasUlids,SoftDeletes; protected $guarded=[]; protected $hidden=['lookup_secret_hash']; protected function casts():array{return ['birth_date'=>'date','consented_at'=>'datetime','submitted_at'=>'datetime','paid_at'=>'datetime','payment_status'=>PaymentStatus::class,'document_status'=>DocumentStatus::class,'selection_status'=>SelectionStatus::class];} public function admissionPeriod(){return $this->belongsTo(AdmissionPeriod::class);} public function documents(){return $this->hasMany(ApplicantDocument::class);} public function payments(){return $this->hasMany(Payment::class);} public function score(){return $this->hasOne(ApplicantScore::class);} }
