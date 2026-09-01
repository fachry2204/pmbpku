<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SelectionStatus;
use App\Http\Controllers\Controller;
use App\Models\AdmissionPeriod;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantScoreController extends Controller
{
    private const SCORE_WEIGHTS = [25, 10, 50, 15];

    private const SCOREABLE_STATUSES = [
        SelectionStatus::AttendingTest->value,
        SelectionStatus::Passed->value,
    ];

    public function index(Request $request, SettingsService $settings): Response
    {
        $query = Applicant::with('score')->whereIn('selection_status', self::SCOREABLE_STATUSES);
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('full_name', 'like', "%{$search}%")->orWhere('registration_number', 'like', "%{$search}%"));
        }
        if ($request->filled('registration_year')) {
            $query->whereHas('admissionPeriod', fn ($period) => $period->where('year', $request->integer('registration_year')));
        }

        $applicants = $query->orderBy('full_name')->paginate(25)->withQueryString()->through(function (Applicant $applicant) {
            $scores = collect([$applicant->score?->score_1, $applicant->score?->score_2, $applicant->score?->score_3, $applicant->score?->score_4]);
            $finalScore = $scores->contains(null)
                ? null
                : $scores->values()->reduce(
                    fn (float $total, mixed $score, int $index) => $total + ((float) $score * self::SCORE_WEIGHTS[$index] / 100),
                    0.0
                );

            return [
                'id' => $applicant->id,
                'registration_number' => $applicant->registration_number,
                'full_name' => $applicant->full_name,
                'score_1' => $applicant->score?->score_1,
                'score_2' => $applicant->score?->score_2,
                'score_3' => $applicant->score?->score_3,
                'score_4' => $applicant->score?->score_4,
                'final_score' => $finalScore === null ? null : round($finalScore, 2),
            ];
        });

        return Inertia::render('Admin/ApplicantScores/Index', [
            'applicants' => $applicants,
            'filters' => $request->only(['search', 'registration_year']),
            'registrationYears' => AdmissionPeriod::query()->distinct()->orderByDesc('year')->pluck('year'),
            'scoreLabels' => collect(range(1, 4))->map(
                fn (int $number) => $settings->get("scores.label_{$number}", $this->defaultScoreLabels()[$number - 1])
            )->values(),
            'scoreWeights' => self::SCORE_WEIGHTS,
        ]);
    }

    private function defaultScoreLabels(): array
    {
        return [
            'Tes Tulis Wawasan Keislaman',
            'Membaca Al Qur’an',
            'Qiroatul Kutub & Muhadatsah Bahasa Arab',
            'Wawancara',
        ];
    }

    public function update(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless(
            in_array($applicant->selection_status->value, self::SCOREABLE_STATUSES, true),
            422,
            'Nilai hanya dapat diberikan kepada pendaftar yang sedang mengikuti seleksi.'
        );
        $data = $request->validate([
            'score_1' => ['required', 'numeric', 'min:0', 'max:100'],
            'score_2' => ['required', 'numeric', 'min:0', 'max:100'],
            'score_3' => ['required', 'numeric', 'min:0', 'max:100'],
            'score_4' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
        ApplicantScore::updateOrCreate(['applicant_id' => $applicant->id], [...$data, 'updated_by' => $request->user()->id]);

        return back()->with('success', 'Nilai '.$applicant->full_name.' berhasil disimpan.');
    }
}
