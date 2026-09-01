<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->ulid('auditable_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // ULID tidak dikembalikan menjadi integer karena akan merusak referensi audit.
    }
};
