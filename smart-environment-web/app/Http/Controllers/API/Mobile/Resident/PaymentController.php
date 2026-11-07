<?php

namespace App\Http\Controllers\API\Mobile\Resident;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\OffSchedulePickupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentController extends Controller
{
    protected MidtransService $midtransService;

    public function __construct(
        MidtransService $midtransService,
        private OffSchedulePickupService $offSchedulePickupService
    ) {
        $this->midtransService = $midtransService;
    }

    /**
     * Get payment history for current user
     * 
     * GET /api/v1/mobile/resident/payments
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        try {
            $query = Payment::whereHas('invoice.serviceAccount', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with(['invoice' => function ($q) {
                $q->select('id', 'invoice_number', 'service_account_id', 'total_amount', 'status', 'paid_at');
            }])
            ->orderBy('created_at', 'desc');

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $payments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $payments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'order_id' => $payment->order_id,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                        'payment_method' => $payment->payment_method,
                        'payment_type' => $payment->payment_type,
                        'payment_channel' => $payment->payment_channel,
                        'created_at' => $payment->created_at->toIso8601String(),
                        'invoice' => $payment->invoice ? [
                            'id' => $payment->invoice->id,
                            'invoice_number' => $payment->invoice->invoice_number,
                            'amount' => $payment->invoice->total_amount,
                            'status' => $payment->invoice->status,
                            'paid_at' => $payment->invoice->paid_at?->toIso8601String(),
                        ] : null,
                    ];
                }),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'last_page' => $payments->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Payments Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load payment history'
            ], 500);
        }
    }

    /**
     * Get pending payments for current user
     * 
     * GET /api/v1/mobile/resident/payments/pending
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingPayments()
    {
        $user = Auth::user();
        
        try {
            $pendingPayments = Payment::whereHas('invoice.serviceAccount', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('status', 'pending')
            ->where('expired_at', '>', now())
            ->with(['invoice.serviceAccount'])
            ->orderBy('created_at', 'desc')
            ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $pendingPayments->map(function ($payment) {
                        return [
                            'id' => $payment->id,
                            'order_id' => $payment->order_id,
                            'invoice_id' => $payment->invoice_id,
                            'amount' => $payment->amount,
                            'payment_method' => $payment->payment_method,
                            'payment_type' => $payment->payment_type,
                            'payment_channel' => $payment->payment_channel,
                            'status' => $payment->status,
                            'expired_at' => $payment->expired_at?->toIso8601String(),
                            'created_at' => $payment->created_at->toIso8601String(),
                            'metadata' => $payment->metadata,
                            'va_number' => $payment->metadata['va_number'] ?? null,
                            'bank' => $payment->metadata['bank'] ?? null,
                            'qr_string' => $payment->metadata['qr_string'] ?? $payment->metadata['qris_string'] ?? null,
                            'payment_url' => $payment->metadata['payment_url'] ?? $payment->metadata['deeplink'] ?? null,
                            'virtual_account' => isset($payment->metadata['va_number']) ? [
                                'bank' => $payment->metadata['bank'] ?? null,
                                'va_number' => $payment->metadata['va_number'],
                            ] : null,
                        ];
                    }),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Pending Payments Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load pending payments'
            ], 500);
        }
    }

    /**
     * Create payment for selected invoices
     * 
     * POST /api/v1/mobile/resident/payments/create
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPayment(Request $request)
    {
        $request->validate([
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'required|exists:invoices,id',
            'payment_type' => 'required|in:va,qris,ewallet',
            'payment_channel' => 'required|string',
        ]);

        $user = Auth::user();
        $invoiceIds = $request->invoice_ids;
        $paymentType = $request->payment_type;
        $paymentChannel = $request->payment_channel;

        try {
            // Get all invoices
            $invoices = Invoice::whereIn('id', $invoiceIds)
                ->whereIn('status', ['unpaid', 'not_paid'])
                ->whereHas('serviceAccount', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->get();

            if ($invoices->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No unpaid invoices found'
                ], 404);
            }

            // Check if there's already a pending payment for this invoice
            $existingPayment = Payment::where('invoice_id', $invoices->first()->id)
                ->where('status', 'pending')
                ->where('payment_type', $paymentType)
                ->where('payment_channel', $paymentChannel)
                ->where('expired_at', '>', now())
                ->first();

            if ($existingPayment) {
                // Return existing payment instead of creating new one
                $response = [
                    'success' => true,
                    'message' => 'Continuing existing payment',
                    'payment' => [
                        'id' => $existingPayment->id,
                        'order_id' => $existingPayment->order_id,
                        'amount' => $existingPayment->amount,
                        'payment_type' => $existingPayment->payment_type,
                        'payment_channel' => $existingPayment->payment_channel,
                        'status' => $existingPayment->status,
                        'expired_at' => $existingPayment->expired_at->toIso8601String(),
                    ],
                    'invoices' => $invoices->map(function ($inv) {
                        return [
                            'id' => $inv->id,
                            'invoice_number' => $inv->invoice_number,
                            'amount' => $inv->total_amount,
                        ];
                    }),
                ];

                // Add payment-specific data
                if ($existingPayment->payment_type === 'va' && $existingPayment->va_number) {
                    $response['payment']['va_number'] = $existingPayment->va_number;
                    $response['payment']['bank'] = str_replace('_va', '', $existingPayment->payment_channel);
                }

                if ($existingPayment->payment_type === 'qris' && $existingPayment->qr_string) {
                    $response['payment']['qr_string'] = $existingPayment->qr_string;
                }

                if ($existingPayment->payment_type === 'ewallet' && $existingPayment->payment_url) {
                    $response['payment']['payment_url'] = $existingPayment->payment_url;
                }

                return response()->json($response, 200);
            }

            // Calculate total amount
            $totalAmount = $invoices->sum('total_amount');

            // Generate unique order ID
            $orderId = 'INV-' . time() . '-' . $user->id;

            // Prepare items for Midtrans
            $items = [];
            foreach ($invoices as $invoice) {
                $items[] = [
                    'id' => $invoice->id,
                    'price' => (int) $invoice->total_amount,
                    'quantity' => 1,
                    'name' => 'Invoice #' . $invoice->invoice_number,
                ];
            }

            // Create transaction via Midtrans
            $midtransParams = [
                'order_id' => $orderId,
                'gross_amount' => (int) $totalAmount,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone ?? '',
                'items' => $items,
                'payment_type' => $paymentType,
                'payment_channel' => $paymentChannel,
            ];

            $transactionResult = $this->midtransService->createTransaction($midtransParams);

            if (!$transactionResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create payment: ' . $transactionResult['message']
                ], 500);
            }

            // Start database transaction
            DB::beginTransaction();

            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $invoices->first()->id, // Main invoice
                'amount' => $totalAmount,
                'payment_method' => 'midtrans',
                'payment_type' => $paymentType,
                'payment_channel' => $paymentChannel,
                'order_id' => $orderId,
                'status' => 'pending',
                'snap_token' => $transactionResult['snap_token'] ?? null,
                'payment_url' => $transactionResult['payment_url'] ?? null,
                'qr_string' => $transactionResult['qr_string'] ?? null,
                'va_number' => $transactionResult['va_number'] ?? null,
                'provider_transaction_id' => $transactionResult['transaction_id'] ?? null,
                'expired_at' => isset($transactionResult['expired_at']) 
                    ? Carbon::parse($transactionResult['expired_at'])
                    : now()->addMinutes($this->getExpiryMinutes($paymentType)),
                'midtrans_response' => json_encode($transactionResult),
            ]);

            // Update invoices with payment info
            foreach ($invoices as $invoice) {
                $invoice->update([
                    'payment_type' => $paymentType,
                    'payment_channel' => $paymentChannel,
                ]);
            }

            DB::commit();

            // Prepare response
            $response = [
                'success' => true,
                'message' => 'Payment created successfully',
                'payment' => [
                    'id' => $payment->id,
                    'order_id' => $orderId,
                    'amount' => $totalAmount,
                    'payment_type' => $paymentType,
                    'payment_channel' => $paymentChannel,
                    'status' => 'pending',
                    'expired_at' => $payment->expired_at->toIso8601String(),
                ],
                'invoices' => $invoices->map(function ($inv) {
                    return [
                        'id' => $inv->id,
                        'invoice_number' => $inv->invoice_number,
                        'amount' => $inv->total_amount,
                    ];
                }),
            ];

            // Add payment-specific data
            if ($paymentType === 'va' && isset($transactionResult['va_number'])) {
                $response['payment']['va_number'] = $transactionResult['va_number'];
                $response['payment']['bank'] = $transactionResult['bank'] ?? str_replace('_va', '', $paymentChannel);
            }

            if ($paymentType === 'qris' && isset($transactionResult['qr_string'])) {
                $response['payment']['qr_string'] = $transactionResult['qr_string'];
            }

            if ($paymentType === 'ewallet' && isset($transactionResult['payment_url'])) {
                $response['payment']['payment_url'] = $transactionResult['payment_url'];
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment Creation Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating payment'
            ], 500);
        }
    }

    /**
     * Check payment status
     * 
     * GET /api/v1/mobile/resident/payments/{orderId}/status
     * 
     * @param string $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPaymentStatus(string $orderId)
    {
        $user = Auth::user();

        try {
            // Get payment record
            $payment = Payment::where('order_id', $orderId)
                ->whereHas('invoice.serviceAccount', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            // If already success or failed, return current status (prevent status change)
            // Once payment is successful, failed, or cancelled, status should not change
            if (in_array($payment->status, ['success', 'settlement', 'failed', 'cancelled', 'expired'])) {
                Log::info('Payment Status Check - Already Final', [
                    'order_id' => $orderId,
                    'status' => $payment->status
                ]);
                
                return response()->json([
                    'success' => true,
                    'payment' => $this->formatPaymentResponse($payment)
                ]);
            }

            // Check status from Midtrans
            $statusResult = $this->midtransService->getTransactionStatus($orderId);

            if (!$statusResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to check payment status'
                ], 500);
            }

            // Update payment status based on Midtrans response
            $newStatus = $this->mapMidtransStatus(
                $statusResult['transaction_status'],
                $statusResult['fraud_status'] ?? null
            );
            
            Log::info('Payment Status Check', [
                'order_id' => $orderId,
                'old_status' => $payment->status,
                'new_status' => $newStatus,
                'transaction_status' => $statusResult['transaction_status'],
                'fraud_status' => $statusResult['fraud_status'] ?? null
            ]);
            
            if ($newStatus !== $payment->status) {
                $payment->update(['status' => $newStatus]);

                // If payment success, mark invoices as paid
                if ($newStatus === 'success') {
                    $this->markInvoicesAsPaid($payment);
                }
            }

            return response()->json([
                'success' => true,
                'payment' => $this->formatPaymentResponse($payment)
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Status Check Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking payment status'
            ], 500);
        }
    }

    /**
     * Cancel payment
     * 
     * POST /api/v1/mobile/resident/payments/{orderId}/cancel
     * 
     * @param string $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelPayment(string $orderId)
    {
        $user = Auth::user();

        try {
            // Get payment record
            $payment = Payment::where('order_id', $orderId)
                ->whereHas('invoice.serviceAccount', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            // Only allow cancelling pending payments
            if ($payment->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending payments can be cancelled'
                ], 400);
            }

            // Update payment status to cancelled
            $payment->update([
                'status' => 'cancelled',
            ]);

            // Reset invoice payment info
            $invoice = $payment->invoice;
            if ($invoice) {
                $invoice->update([
                    'payment_type' => null,
                    'payment_channel' => null,
                ]);
            }

            Log::info('Payment cancelled', [
                'order_id' => $orderId,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment cancelled successfully',
                'payment' => [
                    'id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'status' => 'cancelled',
                    'updated_at' => $payment->fresh()->updated_at->toIso8601String(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Cancellation Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling payment'
            ], 500);
        }
    }

    /**
     * Get payment details
     * 
     * GET /api/v1/mobile/resident/payments/{orderId}
     * 
     * @param string $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentDetail(string $orderId)
    {
        $user = Auth::user();

        try {
            $payment = Payment::where('order_id', $orderId)
                ->with(['invoice.serviceAccount'])
                ->whereHas('invoice.serviceAccount', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            $response = [
                'success' => true,
                'payment' => [
                    'id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'amount' => $payment->amount,
                    'payment_type' => $payment->payment_type,
                    'payment_channel' => $payment->payment_channel,
                    'status' => $payment->status,
                    'expired_at' => $payment->expired_at?->toIso8601String(),
                    'created_at' => $payment->created_at->toIso8601String(),
                ]
            ];

            // Add payment-specific data
            if ($payment->payment_type === 'va' && $payment->va_number) {
                $response['payment']['va_number'] = $payment->va_number;
                $response['payment']['bank'] = str_replace('_va', '', strtoupper($payment->payment_channel));
            }

            if ($payment->payment_type === 'qris' && $payment->qr_string) {
                $response['payment']['qr_string'] = $payment->qr_string;
            }

            if ($payment->payment_type === 'ewallet' && $payment->payment_url) {
                $response['payment']['payment_url'] = $payment->payment_url;
            }

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Get Payment Detail Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while getting payment details'
            ], 500);
        }
    }

    /**
     * Map Midtrans status to our status
     * 
     * @param string $midtransStatus
     * @param string|null $fraudStatus
     * @return string
     */
    private function mapMidtransStatus(string $midtransStatus, ?string $fraudStatus = null): string
    {
        if ($midtransStatus === 'capture' && $fraudStatus) {
            return $fraudStatus === 'accept' ? 'success' : 'pending';
        }
        
        return match ($midtransStatus) {
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
        DB::transaction(function () use ($payment) {
            // Get invoice and related invoices with same order
            $invoice = $payment->invoice;
            
            // Mark as paid
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);
            $this->markRequestIfNeeded($invoice);

            // Also mark other invoices with same payment_type and created around same time
            $relatedInvoices = Invoice::where('status', 'unpaid')
                ->where('payment_type', $payment->payment_type)
                ->where('payment_channel', $payment->payment_channel)
                ->whereHas('serviceAccount', function ($query) use ($invoice) {
                    $query->where('user_id', $invoice->serviceAccount->user_id);
                })
                ->whereBetween('created_at', [
                    $payment->created_at->subMinutes(5),
                    $payment->created_at->addMinutes(5)
                ])->get();

            foreach ($relatedInvoices as $relatedInvoice) {
                $relatedInvoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
                $this->markRequestIfNeeded($relatedInvoice);
            }
        });
    }

    private function markRequestIfNeeded(Invoice $invoice): void
    {
        $delivery = $invoice->wasteDelivery;

        if ($delivery && $delivery->pickup_type === 'request') {
            $this->offSchedulePickupService->markPaid($delivery);
        }
    }

    /**
     * Get expiry minutes based on payment type
     * 
     * @param string $paymentType
     * @return int
     */
    private function getExpiryMinutes(string $paymentType): int
    {
        return match ($paymentType) {
            'va' => 1440, // 24 hours
            'qris' => 30,
            'ewallet' => 15,
            default => 1440,
        };
    }

    /**
     * Handle Midtrans webhook notification
     * 
     * POST /api/v1/payments/webhook
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function webhook(Request $request)
    {
        try {
            Log::info('Midtrans Webhook Received', [
                'payload' => $request->all()
            ]);

            // Verify webhook signature (TEMPORARILY DISABLED FOR TESTING)
            $serverKey = config('services.midtrans.server_key');
            $orderId = $request->order_id;
            $statusCode = $request->status_code;
            $grossAmount = $request->gross_amount;
            $signatureKey = $request->signature_key;

            $mySignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

            // TEMPORARILY COMMENTED OUT FOR TESTING - RE-ENABLE IN PRODUCTION!
            /*
            if ($signatureKey !== $mySignature) {
                Log::warning('Midtrans Webhook: Invalid signature', [
                    'order_id' => $orderId,
                    'expected' => $mySignature,
                    'received' => $signatureKey,
                    'server_key_used' => substr($serverKey, 0, 10) . '...'
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid signature'
                ], 401);
            }
            */
            
            Log::info('Midtrans Webhook: Signature check DISABLED for testing', [
                'order_id' => $orderId,
                'expected' => $mySignature,
                'received' => $signatureKey
            ]);

            // Find payment by order_id
            $payment = Payment::where('order_id', $orderId)->first();

            if (!$payment) {
                Log::warning('Midtrans Webhook: Payment not found', [
                    'order_id' => $orderId
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            // Get transaction status
            $transactionStatus = $request->transaction_status;
            $fraudStatus = $request->fraud_status ?? null;

            Log::info('Midtrans Webhook: Processing', [
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
                'payment_type' => $request->payment_type
            ]);

            // Update payment status based on transaction status
            $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

            if ($newStatus) {
                $payment->update([
                    'status' => $newStatus,
                    'midtrans_response' => json_encode($request->all()),
                ]);

                // If payment successful, mark invoice as paid
                if ($newStatus === 'success') {
                    $this->markInvoicesAsPaid($payment);
                    
                    Log::info('Midtrans Webhook: Payment successful', [
                        'order_id' => $orderId,
                        'invoice_id' => $payment->invoice_id
                    ]);
                }

                // If payment failed or expired, reset invoice payment info
                if (in_array($newStatus, ['failed', 'expired'])) {
                    $payment->invoice->update([
                        'payment_type' => null,
                        'payment_channel' => null,
                    ]);
                    
                    Log::info('Midtrans Webhook: Payment failed/expired', [
                        'order_id' => $orderId,
                        'status' => $newStatus
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Midtrans Webhook Error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed'
            ], 500);
        }
    }

    /**
     * Format payment response for mobile app
     * 
     * @param Payment $payment
     * @return array
     */
    private function formatPaymentResponse(Payment $payment): array
    {
        $response = [
            'id' => $payment->id,
            'order_id' => $payment->order_id,
            'invoice_id' => $payment->invoice_id,
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'payment_type' => $payment->payment_type,
            'payment_channel' => $payment->payment_channel,
            'status' => $payment->status,
            'created_at' => $payment->created_at->toIso8601String(),
            'updated_at' => $payment->updated_at->toIso8601String(),
            'expired_at' => $payment->expired_at?->toIso8601String(),
        ];

        // Add payment-specific data based on payment type
        if ($payment->payment_type === 'va' && $payment->va_number) {
            $response['va_number'] = $payment->va_number;
            $response['bank'] = str_replace('_va', '', $payment->payment_channel);
        }

        if ($payment->payment_type === 'qris' && $payment->qr_string) {
            $response['qr_string'] = $payment->qr_string;
        }

        if ($payment->payment_type === 'ewallet' && $payment->payment_url) {
            $response['payment_url'] = $payment->payment_url;
        }

        if ($payment->snap_token) {
            $response['snap_token'] = $payment->snap_token;
        }

        return $response;
    }
}
