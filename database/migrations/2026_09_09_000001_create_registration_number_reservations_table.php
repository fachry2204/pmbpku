<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_number_reservations', function (Blueprint $table) {
            $table->id();
            // Deliberately no foreign key: the reservation must remain even if
            // an administrator permanently removes the applicant or period.
            $table->unsignedBigInteger('admission_period_id')->index();
            $table->string('registration_prefix', 20);
            $table->unsignedSmallInteger('registration_year');
            $table->unsignedInteger('sequence');
            $table->string('registration_number', 40)->unique();
            $table->timestamp('issued_at');
            $table->timestamps();
            $table->unique(['registration_prefix', 'registration_year', 'sequence'], 'registration_number_sequence_unique');
        });

        // Preserve all numbers that still exist, including soft-deleted
        // applicants. Numbers that were permanently deleted before this
        // migration cannot be reconstructed, but every number issued from now
        // on is permanently reserved.
        DB::table('applicants')->select(['admission_period_id', 'registration_number', 'created_at'])->orderBy('id')->each(function (object $applicant): void {
            if (! preg_match('/^(.*)-(\d{4})-(\d+)$/', $applicant->registration_number, $matches)) {
                return;
            }

            DB::table('registration_number_reservations')->insertOrIgnore([
                'admission_period_id' => $applicant->admission_period_id,
                'registration_prefix' => $matches[1],
                'registration_year' => (int) $matches[2],
                'sequence' => (int) $matches[3],
                'registration_number' => $applicant->registration_number,
                'issued_at' => $applicant->created_at ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_number_reservations');
    }
};
