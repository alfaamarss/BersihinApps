@extends('layouts.master')

@section('title','Edit Pembayaran')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
          <x-button as="a" href="{{ route('admin.payments.index') }}" variant="secondary" size="sm">
            ← Kembali
          </x-button>
        </div>
        <h2 class="text-2xl font-bold text-black dark:text-white">Edit Pembayaran</h2>
        <p class="text-sm text-gray-500 mt-1">Update informasi pembayaran.</p>
      </div>

      <form action="{{ route('admin.payments.update', $payment->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Invoice -->
          <div class="md:col-span-2">
            <label for="invoice_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Tagihan <span class="text-red-500">*</span>
            </label>
            <select name="invoice_id" id="invoice_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('invoice_id') border-red-500 @enderror">
              <option value="">Pilih Tagihan</option>
              @foreach($invoices as $inv)
                <option value="{{ $inv->id }}" {{ (old('invoice_id') ?? $payment->invoice_id) == $inv->id ? 'selected' : '' }}>
                  {{ $inv->invoice_number }} - {{ $inv->serviceAccount?->user?->name ?? $inv->serviceAccount?->name }} - Rp {{ number_format($inv->total_amount, 0, ',', '.') }}
                </option>
              @endforeach
            </select>
            @error('invoice_id')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Amount -->
          <div>
            <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Jumlah (Rp) <span class="text-red-500">*</span>
            </label>
            <input type="number" name="amount" id="amount" value="{{ old('amount', $payment->amount) }}" required min="0" step="1"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('amount') border-red-500 @enderror"
                   placeholder="50000">
            @error('amount')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Payment Type -->
          <div>
            <label for="payment_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Tipe Pembayaran <span class="text-red-500">*</span>
            </label>
            <select name="payment_type" id="payment_type" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('payment_type') border-red-500 @enderror">
              <option value="">Pilih Tipe</option>
              <option value="va" {{ old('payment_type', $payment->payment_type) == 'va' ? 'selected' : '' }}>Virtual Account</option>
              <option value="qris" {{ old('payment_type', $payment->payment_type) == 'qris' ? 'selected' : '' }}>QRIS</option>
              <option value="ewallet" {{ old('payment_type', $payment->payment_type) == 'ewallet' ? 'selected' : '' }}>E-Wallet</option>
            </select>
            @error('payment_type')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Payment Channel (Metode) -->
          <div id="payment_channel_wrapper">
            <label for="payment_channel" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Metode Pembayaran <span class="text-red-500">*</span>
            </label>
            <select name="payment_channel" id="payment_channel" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('payment_channel') border-red-500 @enderror">
              <option value="">Pilih Metode</option>
              <!-- Options will be populated by JavaScript -->
            </select>
            @error('payment_channel')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Hidden field for payment_method (auto-filled based on type) -->
          <input type="hidden" name="payment_method" id="payment_method" value="{{ old('payment_method', $payment->payment_method ?? 'midtrans') }}">

          <!-- Provider Transaction ID -->
          <div>
            <label for="provider_transaction_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Provider Transaction ID
            </label>
            <input type="text" name="provider_transaction_id" id="provider_transaction_id" value="{{ old('provider_transaction_id', $payment->provider_transaction_id) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('provider_transaction_id') border-red-500 @enderror"
                   placeholder="TRX-ABC123">
            @error('provider_transaction_id')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Midtrans Transaction ID -->
          <div>
            <label for="midtrans_transaction_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Midtrans Transaction ID
            </label>
            <input type="text" name="midtrans_transaction_id" id="midtrans_transaction_id" value="{{ old('midtrans_transaction_id', $payment->midtrans_transaction_id) }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('midtrans_transaction_id') border-red-500 @enderror"
                   placeholder="ORDER-123456">
            @error('midtrans_transaction_id')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Status -->
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Status <span class="text-red-500">*</span>
            </label>
            <select name="status" id="status" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('status') border-red-500 @enderror">
              <option value="pending" {{ old('status') ?? $payment->status == 'pending' ? 'selected' : '' }}>Pending</option>
              <option value="settlement" {{ old('status') ?? $payment->status == 'settlement' ? 'selected' : '' }}>Settlement</option>
              <option value="success" {{ old('status') ?? $payment->status == 'success' ? 'selected' : '' }}>Success</option>
              <option value="failed" {{ old('status') ?? $payment->status == 'failed' ? 'selected' : '' }}>Failed</option>
              <option value="cancelled" {{ old('status') ?? $payment->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
              <option value="expired" {{ old('status') ?? $payment->status == 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
            @error('status')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Additional Notes (Optional) -->
          <div class="md:col-span-2">
            <label for="metadata" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Catatan Tambahan (Opsional)
            </label>
            <textarea name="metadata" id="metadata" rows="3"
                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('metadata') border-red-500 @enderror"
                      placeholder='{"notes": "Pembayaran manual via transfer", "verified_by": "Admin"}'>{{ old('metadata', json_encode($payment->midtrans_response ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) }}</textarea>
            @error('metadata')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-xs text-gray-500">
              <strong>Format JSON.</strong> Data ini disimpan di kolom <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-xs">midtrans_response</code> untuk informasi tambahan atau catatan admin.
            </p>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
          <x-button as="a" href="{{ route('admin.payments.index') }}" variant="secondary" size="md">Batal</x-button>
          <x-button type="submit" variant="primary" size="md">Update Pembayaran</x-button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentType = document.getElementById('payment_type');
    const paymentChannel = document.getElementById('payment_channel');
    const paymentChannelWrapper = document.getElementById('payment_channel_wrapper');
    const paymentMethod = document.getElementById('payment_method');
    
    // Define payment channels for each type
    const paymentChannels = {
        'va': [
            { value: 'bca', label: 'BCA VA' },
            { value: 'bni', label: 'BNI VA' },
            { value: 'bri', label: 'BRI VA' },
            { value: 'permata', label: 'Permata VA' }
        ],
        'qris': [],
        'ewallet': [
            { value: 'shopeepay', label: 'ShopeePay' },
            { value: 'gopay', label: 'GoPay' }
        ]
    };
    
    // Current selected values
    const currentPaymentType = '{{ old("payment_type", $payment->payment_type) }}';
    const currentPaymentChannel = '{{ old("payment_channel", $payment->payment_channel) }}';
    
    function updatePaymentChannels() {
        const selectedType = paymentType.value;
        
        // Clear current options
        paymentChannel.innerHTML = '<option value="">Pilih Metode</option>';
        
        if (selectedType === '') {
            // Hide channel selector when no type selected
            paymentChannelWrapper.style.display = 'none';
            paymentChannel.removeAttribute('required');
        } else if (selectedType === 'qris') {
            // Hide channel selector for QRIS (no channels)
            paymentChannelWrapper.style.display = 'none';
            paymentChannel.removeAttribute('required');
            // Set payment_method to qris
            paymentMethod.value = 'qris';
        } else {
            // Show channel selector and populate options
            paymentChannelWrapper.style.display = 'block';
            paymentChannel.setAttribute('required', 'required');
            const channels = paymentChannels[selectedType] || [];
            
            channels.forEach(channel => {
                const option = document.createElement('option');
                option.value = channel.value;
                option.textContent = channel.label;
                paymentChannel.appendChild(option);
            });
            
            // Restore selected value if matches current type
            if (currentPaymentChannel && selectedType === currentPaymentType) {
                paymentChannel.value = currentPaymentChannel;
            }
            
            // Set payment_method to midtrans for VA and ewallet
            paymentMethod.value = 'midtrans';
        }
    }
    
    // Initialize on page load
    updatePaymentChannels();
    
    // Update when type changes
    paymentType.addEventListener('change', function() {
        paymentChannel.value = ''; // Reset channel selection
        updatePaymentChannels();
    });
});
</script>
@endsection
