<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CashfreeController extends Controller
{
    use ApiResponse;

    private function baseUrl(): string
    {
        $env = config('services.cashfree.env', 'sandbox');

        return $env === 'production'
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    private function headers(): array
    {
        return [
            'x-api-version'    => '2023-08-01',
            'x-client-id'      => config('services.cashfree.app_id'),
            'x-client-secret'  => config('services.cashfree.secret_key'),
            'Content-Type'     => 'application/json',
        ];
    }

    public function createOrder(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenant = $request->attributes->get('tenant');
        $plan   = Plan::findOrFail($request->plan_id);

        $orderId = 'SB_' . $tenant->id . '_' . time();
        $amount  = round($plan->price_monthly / 100, 2);

        // Ensure minimum amount of 1 for sandbox
        if ($amount < 1) {
            $amount = 1.00;
        }

        $payload = [
            'order_id'       => $orderId,
            'order_amount'   => $amount,
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id'    => 'TENANT_' . $tenant->id,
                'customer_name'  => $tenant->owner_name ?? $tenant->shop_name,
                'customer_email' => $tenant->email,
                'customer_phone' => $tenant->phone ?? '9999999999',
            ],
        ];

        try {
            $response = Http::withHeaders($this->headers())
                ->post($this->baseUrl() . '/orders', $payload);

            if (!$response->successful()) {
                Log::error('Cashfree createOrder failed', [
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                    'payload'  => $payload,
                ]);
                return $this->error('Failed to create payment order: ' . $response->body(), 502);
            }

            $data = $response->json();

            return $this->success([
                'order_id'           => $data['order_id'] ?? $orderId,
                'payment_session_id' => $data['payment_session_id'] ?? null,
                'amount'             => $amount,
                'plan'               => $plan,
            ], 'Order created');
        } catch (\Throwable $e) {
            Log::error('Cashfree createOrder exception', ['error' => $e->getMessage()]);
            return $this->error('Payment service unavailable', 503);
        }
    }

    public function webhook(Request $request): JsonResponse
    {
        $rawBody   = $request->getContent();
        $timestamp = $request->header('x-webhook-timestamp');
        $signature = $request->header('x-webhook-signature');
        $secretKey = config('services.cashfree.secret_key');

        // Verify signature if secret key is configured
        if ($secretKey && $timestamp && $signature) {
            $expectedSig = hash_hmac('sha256', $timestamp . $rawBody, $secretKey);
            if (!hash_equals($expectedSig, $signature)) {
                Log::warning('Cashfree webhook signature mismatch');
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
            }
        }

        $payload = $request->json()->all();

        $orderData   = $payload['data']['order'] ?? null;
        $paymentData = $payload['data']['payment'] ?? null;

        if (!$orderData) {
            return response()->json(['success' => true, 'message' => 'No order data']);
        }

        $orderId     = $orderData['order_id'] ?? null;
        $orderStatus = $orderData['order_status'] ?? null;
        $cfPaymentId = $paymentData['cf_payment_id'] ?? null;

        if (!$orderId || $orderStatus !== 'PAID') {
            return response()->json(['success' => true, 'message' => 'Payment not completed']);
        }

        // Parse tenant_id from order_id: SB_{tenant_id}_{timestamp}
        $parts    = explode('_', $orderId);
        $tenantId = $parts[1] ?? null;

        if (!$tenantId) {
            Log::error('Cashfree webhook: could not parse tenant_id from order_id', ['order_id' => $orderId]);
            return response()->json(['success' => false, 'message' => 'Invalid order_id format'], 400);
        }

        try {
            $tenant = \App\Models\Tenant::find($tenantId);
            if (!$tenant) {
                Log::error('Cashfree webhook: tenant not found', ['tenant_id' => $tenantId]);
                return response()->json(['success' => false, 'message' => 'Tenant not found'], 404);
            }

            // Find or create subscription by cashfree_order_id
            $subscription = Subscription::where('cashfree_order_id', $orderId)->first();

            if ($subscription) {
                $subscription->update([
                    'cashfree_payment_id' => $cfPaymentId,
                    'status'              => 'active',
                    'starts_at'           => now(),
                    'ends_at'             => now()->addMonth(),
                ]);
            } else {
                Subscription::create([
                    'tenant_id'           => $tenant->id,
                    'plan_id'             => $tenant->plan_id,
                    'cashfree_order_id'   => $orderId,
                    'cashfree_payment_id' => $cfPaymentId,
                    'status'              => 'active',
                    'starts_at'           => now(),
                    'ends_at'             => now()->addMonth(),
                ]);
            }

            $tenant->update([
                'plan_status'      => 'active',
                'plan_renewed_at'  => now(),
                'is_active'        => true,
            ]);

            return response()->json(['success' => true, 'message' => 'Payment processed']);
        } catch (\Throwable $e) {
            Log::error('Cashfree webhook processing failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Processing failed'], 500);
        }
    }

    public function verifyPayment(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|string',
        ]);

        $orderId = $request->order_id;

        try {
            $response = Http::withHeaders($this->headers())
                ->get($this->baseUrl() . '/orders/' . $orderId);

            if (!$response->successful()) {
                return $this->error('Could not verify payment', 502);
            }

            $data          = $response->json();
            $paymentStatus = $data['order_status'] ?? null;

            if ($paymentStatus !== 'PAID') {
                return $this->success([
                    'order_id'       => $orderId,
                    'payment_status' => $paymentStatus,
                    'paid'           => false,
                ], 'Payment not completed');
            }

            // Update subscription and tenant
            $tenant = $request->attributes->get('tenant');

            $subscription = Subscription::where('cashfree_order_id', $orderId)->first();

            $cfPaymentId = null;
            if (isset($data['order_meta']['payment_id'])) {
                $cfPaymentId = $data['order_meta']['payment_id'];
            }

            if ($subscription) {
                $subscription->update([
                    'cashfree_payment_id' => $cfPaymentId,
                    'status'              => 'active',
                    'starts_at'           => now(),
                    'ends_at'             => now()->addMonth(),
                ]);
            } else {
                Subscription::create([
                    'tenant_id'           => $tenant->id,
                    'plan_id'             => $tenant->plan_id,
                    'cashfree_order_id'   => $orderId,
                    'cashfree_payment_id' => $cfPaymentId,
                    'status'              => 'active',
                    'starts_at'           => now(),
                    'ends_at'             => now()->addMonth(),
                ]);
            }

            $tenant->update([
                'plan_status'     => 'active',
                'plan_renewed_at' => now(),
                'is_active'       => true,
            ]);

            return $this->success([
                'order_id'       => $orderId,
                'payment_status' => $paymentStatus,
                'paid'           => true,
            ], 'Payment verified');
        } catch (\Throwable $e) {
            Log::error('Cashfree verifyPayment exception', ['error' => $e->getMessage()]);
            return $this->error('Payment verification failed', 500);
        }
    }
}
