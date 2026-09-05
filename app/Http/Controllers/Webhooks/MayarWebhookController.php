<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;

class MayarWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $event = (string) ($payload['event'] ?? '');
        $data = $payload['data'] ?? [];
        abort_unless($event === 'payment.received' && is_array($data), 422, 'Event Mayar tidak valid.');

        // Mayar documents JSON webhooks without a signature header. Bind the
        // notification to a locally-created payment using its transaction ID,
        // amount and customer email before changing any payment state.
        $reference = (string) ($data['transactionId'] ?? $data['id'] ?? '');
        $amount = (int) ($data['amount'] ?? -1);
        abort_unless($reference !== '' && $amount > 0, 422, 'Data transaksi Mayar tidak lengkap.');

        DB::transaction(function () use ($payload, $data, $reference, $amount, $event) {
            $eventKey = hash('sha256', 'mayar|'.$reference.'|'.($data['updatedAt'] ?? $data['createdAt'] ?? ''));
            if (DB::table('payment_webhook_events')->where('provider', 'mayar')->where('event_key', $eventKey)->exists()) return;

            $payment = Payment::where('provider', 'mayar')->where('provider_reference', $reference)->lockForUpdate()->firstOrFail();
            abort_unless($amount === $payment->total_amount, 422, 'Nominal tidak cocok.');
            abort_unless(strtolower((string) ($data['customerEmail'] ?? '')) === strtolower((string) $payment->applicant->email), 422, 'Email transaksi tidak cocok.');

            $mapped = strtolower((string) ($data['transactionStatus'] ?? $data['status'] ?? '')) === 'paid' ? 'paid' : 'failed';
            $before = $payment->status;
            if ($before !== $mapped) {
                $payment->update(['status' => $mapped, 'paid_at' => $mapped === 'paid' ? now() : $payment->paid_at]);
                $payment->applicant->update(['payment_status' => $mapped, 'paid_at' => $mapped === 'paid' ? now() : null]);
                DB::table('status_histories')->insert(['applicant_id' => $payment->applicant_id, 'dimension' => 'payment', 'from_status' => $before, 'to_status' => $mapped, 'note' => 'Callback Mayar terverifikasi berdasarkan invoice, nominal, dan email.', 'changed_by_type' => null, 'changed_by_id' => null, 'created_at' => now()]);
            }
            DB::table('payment_webhook_events')->insert(['provider' => 'mayar', 'event_key' => $eventKey, 'provider_reference' => $reference, 'event' => $event, 'signature_valid' => true, 'payload_redacted' => json_encode(array_intersect_key($payload, array_flip(['event', 'data']))), 'received_at' => now(), 'processed_at' => now(), 'processing_status' => 'processed', 'created_at' => now(), 'updated_at' => now()]);
        });

        return response()->json(['success' => true]);
    }
}
