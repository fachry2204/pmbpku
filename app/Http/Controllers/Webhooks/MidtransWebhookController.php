<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $serverKey = (string) config('services.midtrans.server_key');
        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        abort_unless($serverKey !== '' && $orderId !== '', 403, 'Kredensial tidak valid.');
        abort_unless(hash_equals($expected, (string) ($payload['signature_key'] ?? '')), 403, 'Signature tidak valid.');

        DB::transaction(function () use ($payload, $orderId, $grossAmount) {
            $transactionStatus = strtolower((string) ($payload['transaction_status'] ?? ''));
            $eventKey = hash('sha256', $orderId.'|'.($payload['transaction_id'] ?? '').'|'.$transactionStatus);
            if (DB::table('payment_webhook_events')->where('provider', 'midtrans')->where('event_key', $eventKey)->exists()) {
                return;
            }

            $payment = Payment::where('provider', 'midtrans')->where('merchant_ref', $orderId)->lockForUpdate()->firstOrFail();
            abort_unless((int) round((float) $grossAmount) === $payment->total_amount, 422, 'Nominal tidak cocok.');

            $mapped = match ($transactionStatus) {
                'settlement' => 'paid',
                'capture' => strtolower((string) ($payload['fraud_status'] ?? 'accept')) === 'accept' ? 'paid' : 'pending',
                'pending', 'authorize' => 'pending',
                'expire' => 'expired',
                'refund', 'partial_refund', 'chargeback', 'partial_chargeback' => 'refunded',
                'deny', 'cancel', 'failure' => 'failed',
                default => 'unpaid',
            };
            $before = $payment->status;

            if ($mapped !== 'unpaid' && $before !== $mapped) {
                $payment->update([
                    'provider_reference' => $payload['transaction_id'] ?? $payment->provider_reference,
                    'payment_method' => $payload['payment_type'] ?? $payment->payment_method,
                    'status' => $mapped,
                    'paid_at' => $mapped === 'paid' ? now() : $payment->paid_at,
                ]);
                $payment->applicant->update([
                    'payment_status' => $mapped,
                    'paid_at' => $mapped === 'paid' ? now() : null,
                ]);
                DB::table('status_histories')->insert([
                    'applicant_id' => $payment->applicant_id,
                    'dimension' => 'payment',
                    'from_status' => $before,
                    'to_status' => $mapped,
                    'note' => 'Callback Midtrans terverifikasi',
                    'changed_by_type' => null,
                    'changed_by_id' => null,
                    'created_at' => now(),
                ]);
            }

            DB::table('payment_webhook_events')->insert([
                'provider' => 'midtrans',
                'event_key' => $eventKey,
                'provider_reference' => $payload['transaction_id'] ?? null,
                'event' => 'payment_status',
                'signature_valid' => true,
                'payload_redacted' => json_encode(array_intersect_key($payload, array_flip([
                    'transaction_id', 'transaction_status', 'status_code', 'payment_type', 'order_id', 'gross_amount', 'fraud_status',
                ]))),
                'received_at' => now(),
                'processed_at' => now(),
                'processing_status' => 'processed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return response()->json(['success' => true]);
    }
}
