<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read_payment')->only('index', 'show', 'downloadReceipt');
        $this->middleware('permission:create_payment')->only('create', 'store');
        $this->middleware('permission:update_payment')->only('edit', 'update');
        $this->middleware('permission:delete_payment')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = Payment::query()
            ->with(['invoice.serviceAccount.user']);

        // Search filter - service account name or user name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('invoice.serviceAccount', function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('name', 'like', '%' . $search . '%');
                  });
            });
        }

        // Payment method filter
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        // Payment type filter
        if ($request->filled('payment_type')) {
            $query->where('payment_type', $request->input('payment_type'));
        }

        // Payment channel filter
        if ($request->filled('payment_channel')) {
            $query->where('payment_channel', $request->input('payment_channel'));
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Invoice filter
        if ($request->filled('invoice_id')) {
            $query->where('invoice_id', $request->input('invoice_id'));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);
        $invoices = Invoice::with('serviceAccount.user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.payments.index', compact('payments', 'invoices'));
    }

    public function create()
    {
        $invoices = Invoice::with('serviceAccount.user')
            ->where('status', 'not_paid')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.payments.create', compact('invoices'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_type' => 'nullable|string',
            'payment_channel' => 'nullable|string',
            'provider_transaction_id' => 'nullable|string',
            'midtrans_transaction_id' => 'nullable|string',
            'status' => 'required|in:pending,settlement,success,failed,cancelled,expired',
            'metadata' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $data = $validator->validated();
            
            // Parse metadata and map to midtrans_response field
            if (isset($data['metadata'])) {
                $data['midtrans_response'] = json_decode($data['metadata'], true);
                unset($data['metadata']); // Remove metadata key
            }

            $payment = Payment::create($data);

            // Update invoice status if payment is successful
            if (in_array($payment->status, ['settlement', 'success'])) {
                $invoice = Invoice::find($payment->invoice_id);
                if ($invoice) {
                    $totalPaid = $invoice->payments()
                        ->whereIn('status', ['settlement', 'success'])
                        ->sum('amount');

                    if ($totalPaid >= $invoice->total_amount) {
                        $invoice->update([
                            'status' => 'paid',
                            'paid_at' => now()
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('admin.payments.index')
                ->with('success', 'Payment berhasil dibuat!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat payment: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(Payment $payment)
    {
        $payment->load(['invoice.serviceAccount.user']);
        $invoices = Invoice::with('serviceAccount.user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.payments.edit', compact('payment', 'invoices'));
    }

    public function update(Request $request, Payment $payment)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'payment_type' => 'nullable|string',
            'payment_channel' => 'nullable|string',
            'provider_transaction_id' => 'nullable|string',
            'midtrans_transaction_id' => 'nullable|string',
            'status' => 'required|in:pending,settlement,success,failed,cancelled,expired',
            'metadata' => 'nullable|json',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $data = $validator->validated();
            
            // Parse metadata and map to midtrans_response field
            if (isset($data['metadata'])) {
                $data['midtrans_response'] = json_decode($data['metadata'], true);
                unset($data['metadata']); // Remove metadata key
            }

            $oldStatus = $payment->status;
            $payment->update($data);

            // Update invoice status if payment status changed to success
            if (!in_array($oldStatus, ['settlement', 'success']) && 
                in_array($payment->status, ['settlement', 'success'])) {
                
                $invoice = Invoice::find($payment->invoice_id);
                if ($invoice) {
                    $totalPaid = $invoice->payments()
                        ->whereIn('status', ['settlement', 'success'])
                        ->sum('amount');

                    if ($totalPaid >= $invoice->total_amount) {
                        $invoice->update([
                            'status' => 'paid',
                            'paid_at' => now()
                        ]);
                    }
                }
            }

            DB::commit();
            return redirect()->route('admin.payments.index')
                ->with('success', 'Payment berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate payment: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Payment $payment)
    {
        DB::beginTransaction();
        try {
            // Only allow deletion of failed/cancelled payments
            if (!in_array($payment->status, ['failed', 'cancelled', 'expired'])) {
                return back()->with('error', 'Hanya payment dengan status failed/cancelled/expired yang dapat dihapus!');
            }

            $payment->delete();

            DB::commit();
            return redirect()->route('admin.payments.index')
                ->with('success', 'Payment berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus payment: ' . $e->getMessage());
        }
    }

    public function show(Payment $payment)
    {
        $payment->load([
            'invoice.serviceAccount.user',
            'invoice.wasteDelivery'
        ]);

        return view('admin.payments.show', compact('payment'));
    }

    public function downloadReceipt(Payment $payment)
    {
        // Only allow download for successful payments
        if (!in_array($payment->status, ['settlement', 'success'])) {
            return back()->with('error', 'Bukti pembayaran hanya dapat diunduh untuk pembayaran yang sudah selesai.');
        }

        $payment->load([
            'invoice.serviceAccount.user',
            'invoice.wasteDelivery'
        ]);

        $appSetting = \App\Models\AppSetting::first();
        
        $pdf = Pdf::loadView('admin.payments.receipt', compact('payment', 'appSetting'));
        
        $filename = 'Bukti_Pembayaran_' . ($payment->midtrans_transaction_id ?? $payment->provider_transaction_id) . '.pdf';
        
        return $pdf->download($filename);
    }
}
