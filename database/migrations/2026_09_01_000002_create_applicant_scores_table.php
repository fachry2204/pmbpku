<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('applicant_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('score_1', 5, 2)->nullable();
            $table->decimal('score_2', 5, 2)->nullable();
            $table->decimal('score_3', 5, 2)->nullable();
            $table->decimal('score_4', 5, 2)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_scores');
    }
};
