<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Duitku\DuitkuSignature;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\DB;

class DuitkuWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $merchantCode = (string) ($payload['merchantCode'] ?? '');
        $merchantOrderId = (string) ($payload['merchantOrderId'] ?? '');
        $amount = (int) ($payload['amount'] ?? -1);
        $signature = (string) ($payload['signature'] ?? '');
        $apiKey = (string) config('services.duitku.api_key');

        abort_unless($apiKey !== '' && $merchantCode === (string) config('services.duitku.merchant_code'), 403, 'Merchant tidak valid.');
        abort_unless(DuitkuSignature::validCallback($merchantCode, $amount, $merchantOrderId, $signature, $apiKey), 403, 'Signature tidak valid.');

        DB::transaction(function () use ($payload, $merchantOrderId, $amount) {
            $eventKey = hash('sha256', $merchantOrderId.'|'.($payload['reference'] ?? '').'|'.($payload['resultCode'] ?? ''));
            if (DB::table('payment_webhook_events')->where('provider', 'duitku')->where('event_key', $eventKey)->exists()) return;

            $payment = Payment::where('provider', 'duitku')->where('merchant_ref', $merchantOrderId)->lockForUpdate()->firstOrFail();
            abort_unless($amount === $payment->total_amount, 422, 'Nominal tidak cocok.');
            if ($payment->provider_reference && isset($payload['reference'])) abort_unless($payload['reference'] === $payment->provider_reference, 422, 'Reference tidak cocok.');

            $mapped = ($payload['resultCode'] ?? '') === '00' ? 'paid' : 'failed';
            $before = $payment->status;
            if ($before !== $mapped) {
                $payment->update(['status' => $mapped, 'paid_at' => $mapped === 'paid' ? now() : $payment->paid_at]);
                $payment->applicant->update(['payment_status' => $mapped, 'paid_at' => $mapped === 'paid' ? now() : null]);
                DB::table('status_histories')->insert([
                    'applicant_id' => $payment->applicant_id, 'dimension' => 'payment', 'from_status' => $before, 'to_status' => $mapped,
                    'note' => 'Callback Duitku terverifikasi', 'changed_by_type' => null, 'changed_by_id' => null, 'created_at' => now(),
                ]);
            }
            DB::table('payment_webhook_events')->insert([
                'provider' => 'duitku', 'event_key' => $eventKey, 'provider_reference' => $payload['reference'] ?? null,
                'event' => 'payment_status', 'signature_valid' => true,
                'payload_redacted' => json_encode(array_intersect_key($payload, array_flip(['merchantCode','amount','merchantOrderId','productDetail','paymentCode','resultCode','reference']))),
                'received_at' => now(), 'processed_at' => now(), 'processing_status' => 'processed', 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return response()->json(['success' => true]);
    }
}
