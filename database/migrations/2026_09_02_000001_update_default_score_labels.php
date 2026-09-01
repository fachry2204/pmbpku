<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $labels = [
            'scores.label_1' => ['Nilai 1', 'Tes Tulis Wawasan Keislaman'],
            'scores.label_2' => ['Nilai 2', 'Membaca Al Qur’an'],
            'scores.label_3' => ['Nilai 3', 'Qiroatul Kutub & Muhadatsah Bahasa Arab'],
            'scores.label_4' => ['Nilai 4', 'Wawancara'],
        ];

        foreach ($labels as $key => [$oldLabel, $newLabel]) {
            DB::table('settings')->where('key', $key)->where('value', $oldLabel)->update([
                'value' => $newLabel,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Labels are user-editable settings, so a rollback must not overwrite
        // a label that an administrator may have changed after this migration.
    }
};
