<?php

namespace Database\Seeders;

use App\Models\AdmissionPeriod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        AdmissionPeriod::firstOrCreate(['name' => 'PMB 2026'], [
            'year' => 2026,
            'registration_prefix' => 'PKU',
            'starts_at' => now()->startOfYear(),
            'ends_at' => now()->endOfYear(),
            'quota' => 100,
            'registration_fee' => 250000,
            'is_active' => true,
        ]);
    }
}
