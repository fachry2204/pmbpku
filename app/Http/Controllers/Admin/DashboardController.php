<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $paidApplicants = Applicant::query()->where('payment_status', 'paid');

        return Inertia::render('Admin/Dashboard', ['stats' => [
            'total' => Applicant::count(),
            'paid' => (clone $paidApplicants)->count(),
            'unpaid' => Applicant::where('payment_status', 'unpaid')->count(),
            'pending_documents' => Applicant::where('document_status', 'pending_review')->count(),
            'complete_documents' => Applicant::where('document_status', 'complete')->count(),
            'passed' => Applicant::where('selection_status', 'passed')->count(),
            // Pendapatan dihitung dari biaya pendaftaran periode masing-masing pendaftar yang sudah lunas.
            'revenue' => (int) (clone $paidApplicants)
                ->join('admission_periods', 'admission_periods.id', '=', 'applicants.admission_period_id')
                ->sum('admission_periods.registration_fee'),
        ]]);
    }
}
