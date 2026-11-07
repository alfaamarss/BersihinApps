<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use App\Services\MidtransService;
use App\Services\OffSchedulePickupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    protected MidtransService $midtransService;

    public function __construct(
        MidtransService $midtransService,
        private OffSchedulePickupService $offSchedulePickupService
    ) {
        $this->midtransService = $midtransService;
    }

    /**
     * Handle Midtrans payment notification
     * 
     * POST /api/midtrans/webhook
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleNotification(Request $request)
    {
        try {
            $notification = $request->all();
            
            Log::info('Midtrans Notification Received', $notification);

            // Verify signature
            if (!$this->midtransService->verifySignature($notification)) {
                Log::warning('Invalid Midtrans signature', $notification);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 403);
            }

            $orderId = $notification['order_id'];
            $transactionStatus = $notification['transaction_status'];
            $fraudStatus = $notification['fraud_status'] ?? 'accept';

            // Get payment record
            $payment = Payment::where('order_id', $orderId)->first();

            if (!$payment) {
                Log::warning('Payment not found for order: ' . $orderId);
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            // Determine payment status
            $newStatus = $this->determinePaymentStatus($transactionStatus, $fraudStatus);

            // Update payment
            DB::transaction(function () use ($payment, $newStatus, $notification) {
                $payment->update([
                    'status' => $newStatus,
                    'provider_transaction_id' => $notification['transaction_id'] ?? $payment->provider_transaction_id,
                    'midtrans_response' => json_encode($notification),
                ]);

                // If payment success, mark invoices as paid
                if ($newStatus === 'success') {
                    $this->markInvoicesAsPaid($payment);
                }

                // If payment failed or expired, mark invoices back to unpaid
                if (in_array($newStatus, ['failed', 'expired'])) {
                    $this->markInvoicesAsUnpaid($payment);
                }
            });

            Log::info('Payment status updated', [
                'order_id' => $orderId,
                'status' => $newStatus,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Notification processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Midtrans Webhook Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Determine payment status based on Midtrans status
     * 
     * @param string $transactionStatus
     * @param string $fraudStatus
     * @return string
     */
    private function determinePaymentStatus(string $transactionStatus, string $fraudStatus): string
    {
        // Handle capture transaction
        if ($transactionStatus === 'capture') {
            return $fraudStatus === 'accept' ? 'success' : 'failed';
        }

        // Map other statuses
        return match ($transactionStatus) {
            'settlement' => 'success',
            'pending' => 'pending',
            'deny', 'cancel', 'refund' => 'failed',
            'expire' => 'expired',
            default => 'pending',
        };
    }

    /**
     * Mark invoices as paid
     * 
     * @param Payment $payment
     * @return void
     */
    private function markInvoicesAsPaid(Payment $payment): void
    {
        // Get the main invoice
        $invoice = $payment->invoice;
        
        if ($invoice && $invoice->status === 'unpaid') {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            $this->markRequestIfNeeded($invoice);

            Log::info('Invoice marked as paid', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'order_id' => $payment->order_id,
            ]);
        }

        // Mark other related invoices (same payment session)
        $relatedInvoices = Invoice::where('status', 'unpaid')
            ->where('payment_type', $payment->payment_type)
            ->where('payment_channel', $payment->payment_channel)
            ->whereHas('serviceAccount', function ($query) use ($invoice) {
                $query->where('user_id', $invoice->serviceAccount->user_id);
            })
            ->whereBetween('updated_at', [
                $payment->created_at->subMinutes(5),
                $payment->created_at->addMinutes(5)
            ])
            ->get();

        foreach ($relatedInvoices as $relInvoice) {
            $relInvoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            $this->markRequestIfNeeded($relInvoice);

            Log::info('Related invoice marked as paid', [
                'invoice_id' => $relInvoice->id,
                'invoice_number' => $relInvoice->invoice_number,
            ]);
        }
    }

    private function markRequestIfNeeded(Invoice $invoice): void
    {
        $delivery = $invoice->wasteDelivery;

        if ($delivery && $delivery->pickup_type === 'request') {
            $this->offSchedulePickupService->markPaid($delivery);
        }
    }

    /**
     * Mark invoices as unpaid (when payment failed/expired)
     * 
     * @param Payment $payment
     * @return void
     */
    private function markInvoicesAsUnpaid(Payment $payment): void
    {
        $invoice = $payment->invoice;
        
        if ($invoice && $invoice->status === 'paid') {
            $invoice->update([
                'status' => 'unpaid',
                'paid_at' => null,
                'payment_type' => null,
                'payment_channel' => null,
            ]);

            Log::info('Invoice marked as unpaid', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'reason' => 'Payment ' . $payment->status,
            ]);
        }
    }

    /**
     * Test webhook (for development)
     * 
     * GET /api/midtrans/webhook/test
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function test()
    {
        return response()->json([
            'success' => true,
            'message' => 'Midtrans webhook endpoint is working',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
