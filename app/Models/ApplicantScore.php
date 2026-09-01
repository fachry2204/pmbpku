<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicantScore extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['score_1'=>'float','score_2'=>'float','score_3'=>'float','score_4'=>'float'];
    }

    public function applicant()
    {
        return $this->belongsTo(Applicant::class);
    }
}
