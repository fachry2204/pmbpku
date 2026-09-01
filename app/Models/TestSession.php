<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TestSession extends Model { protected $guarded=[]; protected function casts():array{return ['starts_at'=>'datetime','ends_at'=>'datetime'];} public function applicants(){return $this->belongsToMany(Applicant::class,'applicant_test_sessions')->withPivot(['attendance_status','score','internal_note','assigned_at','attended_at'])->withTimestamps();} }
