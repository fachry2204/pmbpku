<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->string('status')->toString();
        abort_unless(in_array($status, ['', 'paid', 'unpaid'], true), 422);

        $query = Payment::with('applicant:id,registration_number,full_name,payment_status')->latest();
        if ($status === 'paid') {
            $query->whereHas('applicant', fn ($applicant) => $applicant->where('payment_status', 'paid'));
        }
        if ($status === 'unpaid') {
            $query->whereHas('applicant', fn ($applicant) => $applicant->where('payment_status', '!=', 'paid'));
        }
        if ($request->filled('provider')) {
            $query->where('provider', $request->string('provider')->toString());
        }

        $summaryQuery = Applicant::query()->whereHas('payments');

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $query->paginate(25)->withQueryString(),
            'filters' => $request->only(['status', 'provider']),
            'statusSummary' => [
                'all' => (clone $summaryQuery)->count(),
                'paid' => (clone $summaryQuery)->where('payment_status', 'paid')->count(),
                'unpaid' => (clone $summaryQuery)->where('payment_status', '!=', 'paid')->count(),
            ],
        ]);
    }

    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->provider === 'manual', 422);
        $data = $request->validate([
            'decision' => ['required', 'in:accept,reject'],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:decision,reject'],
        ]);

        DB::transaction(function () use ($request, $payment, $data): void {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $status = $data['decision'] === 'accept' ? 'paid' : 'failed';
            $payment->update([
                'status' => $status,
                'paid_at' => $status === 'paid' ? now() : null,
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'verification_note' => $data['note'] ?? null,
            ]);
            $payment->applicant->update(['payment_status' => $status, 'paid_at' => $status === 'paid' ? now() : null]);
            DB::table('status_histories')->insert([
                'applicant_id' => $payment->applicant_id,
                'dimension' => 'payment',
                'from_status' => 'pending',
                'to_status' => $status,
                'note' => $data['note'] ?? 'Verifikasi pembayaran manual',
                'changed_by_type' => 'user',
                'changed_by_id' => $request->user()->id,
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Pembayaran manual diperbarui.');
    }
}
