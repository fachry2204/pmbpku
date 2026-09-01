<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model { use HasUlids; protected $guarded=[]; protected function casts():array{return ['expires_at'=>'datetime','paid_at'=>'datetime','instructions_json'=>'array','request_payload_redacted'=>'array','response_payload_redacted'=>'array'];} public function applicant(){return $this->belongsTo(Applicant::class);} }
