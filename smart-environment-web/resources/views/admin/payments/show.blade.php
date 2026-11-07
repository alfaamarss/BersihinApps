@extends('layouts.master')

@section('title','Detail Pembayaran')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
          <x-button as="a" href="{{ route('admin.payments.index') }}" variant="secondary" size="sm">
            ← Kembali
          </x-button>
          <div>
            <h2 class="text-2xl font-bold text-black dark:text-white">Detail Pembayaran</h2>
            <p class="text-sm text-gray-500 mt-1">Informasi lengkap transaksi pembayaran</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          @if(in_array($payment->status, ['settlement', 'success']))
            <x-button as="a" href="{{ route('admin.payments.downloadReceipt', $payment->id) }}" variant="primary" size="md">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Download Bukti
            </x-button>
          @endif
          <!-- <x-button as="a" href="{{ route('admin.payments.edit', $payment->id) }}" variant="secondary" size="md">Edit</x-button> -->
        </div>
      </div>

      <!-- Payment Information -->
      <div class="space-y-6">
        <!-- Header Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Transaction ID</p>
            <p class="text-base font-semibold text-black dark:text-white mt-1 font-mono">
              {{ $payment->midtrans_transaction_id ?? $payment->provider_transaction_id ?? '-' }}
            </p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Jumlah</p>
            <p class="text-lg font-bold text-blue-600 dark:text-blue-400 mt-1">Rp {{ number_format($payment->amount, 0, ',', '.') }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
            <div class="mt-1">
              @if(in_array($payment->status, ['settlement', 'success']))
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                  {{ ucfirst($payment->status) }}
                </span>
              @elseif($payment->status === 'pending')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                  Pending
                </span>
              @elseif($payment->status === 'expired')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                  Expired
                </span>
              @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                  {{ ucfirst($payment->status) }}
                </span>
              @endif
            </div>
          </div>
        </div>

        <!-- Payment Details -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
          <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Detail Transaksi</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Metode Pembayaran</p>
              <p class="text-base font-medium text-black dark:text-white mt-1">
                {{ strtoupper(str_replace('_', ' ', $payment->payment_method ?? '-')) }}
              </p>
            </div>
            @if($payment->payment_type)
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Tipe Pembayaran</p>
                <p class="text-base font-medium text-black dark:text-white mt-1">{{ ucfirst($payment->payment_type) }}</p>
              </div>
            @endif
            @if($payment->payment_channel)
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Channel</p>
                <p class="text-base font-medium text-black dark:text-white mt-1">{{ strtoupper($payment->payment_channel) }}</p>
              </div>
            @endif
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Waktu Transaksi</p>
              <p class="text-base font-medium text-black dark:text-white mt-1">
                {{ $payment->created_at->format('d M Y, H:i:s') }}
              </p>
            </div>
            @if($payment->provider_transaction_id)
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Provider Transaction ID</p>
                <p class="text-base font-mono text-black dark:text-white mt-1">{{ $payment->provider_transaction_id }}</p>
              </div>
            @endif
            @if($payment->midtrans_transaction_id)
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Midtrans Transaction ID</p>
                <p class="text-base font-mono text-black dark:text-white mt-1">{{ $payment->midtrans_transaction_id }}</p>
              </div>
            @endif
          </div>
        </div>

        <!-- Invoice Information -->
        @if($payment->invoice)
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Informasi Tagihan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nomor Tagihan</p>
                <p class="text-base font-medium text-black dark:text-white mt-1">
                  <a href="{{ route('admin.invoices.show', $payment->invoice->id) }}" class="text-blue-600 hover:underline">
                    {{ $payment->invoice->invoice_number }}
                  </a>
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Tagihan</p>
                <p class="text-base font-medium text-black dark:text-white mt-1">
                  Rp {{ number_format($payment->invoice->total_amount, 0, ',', '.') }}
                </p>
              </div>
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Status Tagihan</p>
                <div class="mt-1">
                  @if($payment->invoice->status === 'paid')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      Lunas
                    </span>
                  @elseif($payment->invoice->status === 'not_paid')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                      Belum Dibayar
                    </span>
                  @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                      Dibatalkan
                    </span>
                  @endif
                </div>
              </div>
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Pelanggan</p>
                <p class="text-base font-medium text-black dark:text-white mt-1">
                  {{ $payment->invoice->serviceAccount?->user?->name ?? $payment->invoice->serviceAccount?->name ?? '-' }}
                </p>
              </div>
            </div>
          </div>
        @endif

        <!-- Waste Delivery Information -->
        @if($payment->invoice?->wasteDelivery)
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Informasi Pengiriman Sampah</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Delivery ID</p>
                <p class="text-base font-medium text-black dark:text-white mt-1">#{{ $payment->invoice->waste_delivery_id }}</p>
              </div>
            </div>
          </div>
        @endif

        <!-- Metadata -->
        @if($payment->metadata)
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Metadata</h3>
            <div class="p-4 bg-gray-50 dark:bg-gray-800/30 rounded-lg">
              <pre class="text-sm text-black dark:text-white overflow-x-auto">{{ json_encode($payment->metadata, JSON_PRETTY_PRINT) }}</pre>
            </div>
          </div>
        @endif

        <!-- Timestamps -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
          <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Riwayat</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Dibuat</p>
              <p class="text-base font-medium text-black dark:text-white mt-1">
                {{ $payment->created_at->format('d M Y, H:i:s') }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Terakhir Diupdate</p>
              <p class="text-base font-medium text-black dark:text-white mt-1">
                {{ $payment->updated_at->format('d M Y, H:i:s') }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
