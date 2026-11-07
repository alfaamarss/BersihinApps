<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\ServiceAccount;
use App\Models\WasteDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read_invoice')->only('index', 'show');
        $this->middleware('permission:create_invoice')->only('create', 'store');
        $this->middleware('permission:update_invoice')->only('edit', 'update');
        $this->middleware('permission:delete_invoice')->only('destroy');
    }

    public function index(Request $request)
    {
        $query = Invoice::query()
            ->with(['serviceAccount.user', 'wasteDelivery'])
            ->withCount('payments');

        // Search filter - customer name or service account name
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->whereHas('serviceAccount', function($q) use ($searchTerm) {
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('user', function($userQuery) use ($searchTerm) {
                      $userQuery->where('name', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Service Account filter
        if ($request->filled('service_account_id')) {
            $query->where('service_account_id', $request->input('service_account_id'));
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('issued_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('issued_at', '<=', $request->input('date_to'));
        }

        $invoices = $query->orderBy('issued_at', 'desc')->paginate(15);
        $serviceAccounts = ServiceAccount::with('user')->orderBy('created_at', 'desc')->get();

        return view('admin.invoices.index', compact('invoices', 'serviceAccounts'));
    }

    public function create()
    {
        $serviceAccounts = ServiceAccount::with('user')->orderBy('created_at', 'desc')->get();
        $wasteDeliveries = WasteDelivery::whereDoesntHave('invoice')
            ->with('serviceAccount.user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.invoices.create', compact('serviceAccounts', 'wasteDeliveries'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_account_id' => 'required|exists:service_accounts,id',
            'waste_delivery_id' => 'nullable|exists:waste_deliveries,id',
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|in:not_paid,paid,cancelled',
            'issued_at' => 'required|date',
            'paid_at' => 'nullable|date',
            'va_number' => 'nullable|string',
            'va_expiry_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $payload = $validator->validated();

            // Default jatuh tempo ke akhir bulan jika tidak diisi agar sinkron dengan pengingat H-3 s/d H.
            if (empty($payload['va_expiry_at'])) {
                $payload['va_expiry_at'] = Carbon::now()->endOfMonth()->endOfDay();
            }

            $invoice = Invoice::create($payload);

            DB::commit();
            return redirect()->route('admin.invoices.index')
                ->with('success', 'Invoice berhasil dibuat!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membuat invoice: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load(['serviceAccount.user', 'wasteDelivery', 'payments']);
        $serviceAccounts = ServiceAccount::with('user')->orderBy('created_at', 'desc')->get();
        $wasteDeliveries = WasteDelivery::with('serviceAccount.user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.invoices.edit', compact('invoice', 'serviceAccounts', 'wasteDeliveries'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $validator = Validator::make($request->all(), [
            'service_account_id' => 'required|exists:service_accounts,id',
            'waste_delivery_id' => 'nullable|exists:waste_deliveries,id',
            'invoice_number' => 'required|string|unique:invoices,invoice_number,' . $invoice->id,
            'total_amount' => 'required|numeric|min:0',
            'status' => 'required|in:not_paid,paid,cancelled',
            'issued_at' => 'required|date',
            'paid_at' => 'nullable|date',
            'va_number' => 'nullable|string',
            'va_expiry_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $invoice->update($validator->validated());

            DB::commit();
            return redirect()->route('admin.invoices.index')
                ->with('success', 'Invoice berhasil diupdate!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengupdate invoice: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Invoice $invoice)
    {
        DB::beginTransaction();
        try {
            // Check if invoice has payments
            if ($invoice->payments()->exists()) {
                return back()->with('error', 'Invoice tidak dapat dihapus karena sudah memiliki pembayaran!');
            }

            $invoice->delete();

            DB::commit();
            return redirect()->route('admin.invoices.index')
                ->with('success', 'Invoice berhasil dihapus!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus invoice: ' . $e->getMessage());
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'serviceAccount.user',
            'wasteDelivery.wasteDeliveryItems.waste',
            'wasteDelivery.wasteDeliveryItems.pocketSize',
            'payments'
        ]);

        return view('admin.invoices.show', compact('invoice'));
    }
}
