@extends('layouts.master')

@section('title','Pembayaran')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Pembayaran</h2>
          <p class="text-sm text-gray-500 mt-1">Kelola data transaksi pembayaran.</p>
        </div>
        <!-- <div class="flex items-center gap-3">
          <x-button as="a" href="{{ route('admin.payments.create') }}" variant="primary" size="md">Tambah Pembayaran</x-button>
        </div> -->
      </div>

      <!-- Filter Section -->
      <div class="mb-4">
        <form method="GET" action="{{ route('admin.payments.index') }}" class="flex flex-wrap gap-3" id="paymentFilterForm">
          <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama service account atau user..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
          </div>
          <div class="min-w-[150px]">
            <select name="payment_type" id="paymentType" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
              <option value="">Semua Tipe</option>
              <option value="va" {{ request('payment_type') == 'va' ? 'selected' : '' }}>Virtual Account</option>
              <option value="qris" {{ request('payment_type') == 'qris' ? 'selected' : '' }}>QRIS</option>
              <option value="ewallet" {{ request('payment_type') == 'ewallet' ? 'selected' : '' }}>E-Wallet</option>
            </select>
          </div>
          <div class="min-w-[150px]" id="paymentChannelWrapper">
            <select name="payment_channel" id="paymentChannel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
              <option value="">Pilih Metode</option>
              <!-- Options will be populated dynamically -->
            </select>
          </div>
          <div class="min-w-[150px]">
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
              <option value="">Semua Status</option>
              <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
              <option value="settlement" {{ request('status') == 'settlement' ? 'selected' : '' }}>Settlement</option>
              <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Success</option>
              <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
              <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
              <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
          </div>
          <div class="min-w-[140px]">
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   placeholder="Dari Tanggal"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
          </div>
          <div class="min-w-[140px]">
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   placeholder="Sampai Tanggal"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
          </div>
          <x-button type="submit" variant="secondary" size="md">Filter</x-button>
          @if(request()->hasAny(['search', 'payment_method', 'payment_type', 'payment_channel', 'status', 'date_from', 'date_to']))
            <x-button as="a" href="{{ route('admin.payments.index') }}" variant="secondary" size="md">Reset</x-button>
          @endif
        </form>
      </div>

      <x-table :headers="[
          ['label' => 'User','class'=>'min-w-[180px]'],
          ['label' => 'Tagihan','class'=>'min-w-[140px]'],
          ['label' => 'Jumlah','class'=>'min-w-[120px]'],
          ['label' => 'Metode','class'=>'min-w-[120px]'],
          ['label' => 'Channel','class'=>'min-w-[100px]'],
          ['label' => 'Status','class'=>'min-w-[100px]'],
          ['label' => 'Waktu','class'=>'min-w-[120px]'],
          ['label' => 'Actions']
      ]" empty="Tidak ada data pembayaran.">
        @foreach($payments ?? [] as $payment)
          <x-table.row>
            <x-table.cell>
              <div class="font-medium text-sm">{{ $payment->invoice?->serviceAccount?->name ?? '-' }}</div>
              @if($payment->invoice?->serviceAccount?->user)
                <div class="text-xs text-gray-500 mt-1">
                  {{ $payment->invoice->serviceAccount->user->name }}
                </div>
              @endif
            </x-table.cell>
            <x-table.cell>
              <div class="font-medium">{{ $payment->invoice?->invoice_number ?? '-' }}</div>
              <div class="text-xs text-gray-500 mt-1">
                {{ $payment->midtrans_transaction_id ?? $payment->provider_transaction_id ?? '-' }}
              </div>
            </x-table.cell>
            <x-table.cell>
              <div class="font-semibold text-blue-600">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
            </x-table.cell>
            <x-table.cell>
              <div class="text-sm font-medium">{{ strtoupper(str_replace('_', ' ', $payment->payment_method ?? '-')) }}</div>
              @if($payment->payment_type)
                <div class="text-xs text-gray-500">{{ ucfirst($payment->payment_type) }}</div>
              @endif
            </x-table.cell>
            <x-table.cell muted>
              {{ $payment->payment_channel ? strtoupper($payment->payment_channel) : '-' }}
            </x-table.cell>
            <x-table.cell>
              @if(in_array($payment->status, ['settlement', 'success']))
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                  {{ ucfirst($payment->status) }}
                </span>
              @elseif($payment->status === 'pending')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                  Pending
                </span>
              @elseif($payment->status === 'expired')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                  Expired
                </span>
              @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                  {{ ucfirst($payment->status) }}
                </span>
              @endif
            </x-table.cell>
            <x-table.cell muted>
              {{ $payment->created_at->format('d M Y') }}
              <div class="text-xs text-gray-400">{{ $payment->created_at->format('H:i') }}</div>
            </x-table.cell>
            <x-table.cell>
              <div class="flex items-center gap-2">
                <x-button as="a" href="{{ route('admin.payments.show', $payment->id) }}" variant="secondary" size="sm">Detail</x-button>
                @if(in_array($payment->status, ['settlement', 'success']))
                  <x-button as="a" href="{{ route('admin.payments.downloadReceipt', $payment->id) }}" variant="primary" size="sm" title="Download Bukti Pembayaran">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                  </x-button>
                @endif
                <!-- <x-button as="a" href="{{ route('admin.payments.edit', $payment->id) }}" variant="secondary" size="sm">Edit</x-button> -->
                @if(in_array($payment->status, ['failed', 'cancelled', 'expired']))
                  <form action="{{ route('admin.payments.destroy', $payment->id) }}" method="POST" class="inline"
                        onsubmit="return confirm('Yakin ingin menghapus pembayaran ini?')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" size="sm">Hapus</x-button>
                  </form>
                @endif
              </div>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>

      <!-- Pagination -->
      @if(isset($payments) && $payments->hasPages())
        <div class="mt-6">
          {{ $payments->links() }}
        </div>
      @endif
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentType = document.getElementById('paymentType');
    const paymentChannel = document.getElementById('paymentChannel');
    const paymentChannelWrapper = document.getElementById('paymentChannelWrapper');
    
    // Define payment channels for each type (sesuai dengan data di database)
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
    
    // Current selected values from request
    const currentPaymentType = '{{ request("payment_type") }}';
    const currentPaymentChannel = '{{ request("payment_channel") }}';
    
    function updatePaymentChannels() {
        const selectedType = paymentType.value;
        
        // Clear current options
        paymentChannel.innerHTML = '<option value="">Pilih Metode</option>';
        
        if (selectedType === '') {
            // Hide channel selector when no type selected
            paymentChannelWrapper.style.display = 'none';
        } else if (selectedType === 'qris') {
            // Hide channel selector for QRIS (no channels)
            paymentChannelWrapper.style.display = 'none';
        } else {
            // Show channel selector and populate options
            paymentChannelWrapper.style.display = 'block';
            const channels = paymentChannels[selectedType] || [];
            
            channels.forEach(channel => {
                const option = document.createElement('option');
                option.value = channel.value;
                option.textContent = channel.label;
                paymentChannel.appendChild(option);
            });
        }
        
        // Restore selected value if matches current type
        if (currentPaymentChannel && selectedType === currentPaymentType) {
            paymentChannel.value = currentPaymentChannel;
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
