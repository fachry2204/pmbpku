<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\ApplicantScore;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantScoreController extends Controller
{
    public function index(Request $request, SettingsService $settings): Response
    {
        $query = Applicant::with('score')->where('selection_status', 'passed');
        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('full_name', 'like', "%{$search}%")->orWhere('registration_number', 'like', "%{$search}%"));
        }

        $applicants = $query->orderBy('full_name')->paginate(25)->withQueryString()->through(function (Applicant $applicant) {
            $scores = collect([$applicant->score?->score_1, $applicant->score?->score_2, $applicant->score?->score_3, $applicant->score?->score_4]);

            return [
                'id' => $applicant->id,
                'registration_number' => $applicant->registration_number,
                'full_name' => $applicant->full_name,
                'score_1' => $applicant->score?->score_1,
                'score_2' => $applicant->score?->score_2,
                'score_3' => $applicant->score?->score_3,
                'score_4' => $applicant->score?->score_4,
                'average' => $scores->contains(null) ? null : round((float) $scores->average(), 2),
            ];
        });

        return Inertia::render('Admin/ApplicantScores/Index', [
            'applicants' => $applicants,
            'filters' => $request->only('search'),
            'scoreLabels' => collect(range(1, 4))->map(
                fn (int $number) => $settings->get("scores.label_{$number}", "Nilai {$number}")
            )->values(),
        ]);
    }

    public function update(Request $request, Applicant $applicant): RedirectResponse
    {
        abort_unless($applicant->selection_status->value === 'passed', 422, 'Nilai hanya dapat diberikan kepada pendaftar yang sudah diterima.');
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
