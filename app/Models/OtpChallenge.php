<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
class OtpChallenge extends Model { use HasUlids; protected $guarded=[]; protected $hidden=['code_hash','ip_hash']; protected function casts():array{return ['expires_at'=>'datetime','consumed_at'=>'datetime'];} public function applicant(){return $this->belongsTo(Applicant::class);} }
