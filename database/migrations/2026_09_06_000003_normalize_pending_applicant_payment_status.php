<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pending is used by a gateway transaction, not by the applicant status.
        DB::table('applicants')
            ->where('payment_status', 'pending')
            ->update(['payment_status' => 'unpaid', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Deliberately no-op: previous pending records cannot be distinguished
        // from records that were unpaid before this normalization.
    }
};
