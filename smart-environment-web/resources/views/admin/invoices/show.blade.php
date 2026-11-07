@extends('layouts.master')

@section('title','Detail Tagihan')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
          <x-button as="a" href="{{ route('admin.invoices.index') }}" variant="secondary" size="sm">
            ← Kembali
          </x-button>
          <div>
            <h2 class="text-2xl font-bold text-black dark:text-white">Detail Tagihan</h2>
            <p class="text-sm text-gray-500 mt-1">Informasi lengkap tagihan</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <!-- <x-button as="a" href="{{ route('admin.invoices.edit', $invoice->id) }}" variant="secondary" size="md">Edit</x-button> -->
        </div>
      </div>

      <!-- Invoice Information -->
      <div class="space-y-6">
        <!-- Header Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Nomor Tagihan</p>
            <p class="text-lg font-semibold text-black dark:text-white mt-1">{{ $invoice->invoice_number }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Total Tagihan</p>
            <p class="text-lg font-bold text-blue-600 dark:text-blue-400 mt-1">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</p>
          </div>
          <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
            <div class="mt-1">
              @if($invoice->status === 'paid')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                  Lunas
                </span>
              @elseif($invoice->status === 'not_paid')
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                  Belum Dibayar
                </span>
              @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                  Dibatalkan
                </span>
              @endif
            </div>
          </div>
        </div>

        <!-- Customer Information -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
          <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Informasi Pelanggan</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Nama</p>
              <p class="text-base font-medium text-black dark:text-white mt-1">
                {{ $invoice->serviceAccount?->user?->name ?? $invoice->serviceAccount?->name ?? '-' }}
              </p>
            </div>
            @if($invoice->serviceAccount?->user)
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                <p class="text-base font-medium text-black dark:text-white mt-1">{{ $invoice->serviceAccount->user->email }}</p>
              </div>
            @endif
            @if($invoice->serviceAccount?->address)
              <div class="md:col-span-2">
                <p class="text-sm text-gray-500 dark:text-gray-400">Alamat</p>
                <p class="text-base font-medium text-black dark:text-white mt-1">{{ $invoice->serviceAccount->address }}</p>
              </div>
            @endif
          </div>
        </div>

        <!-- Payment Information -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
          <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Informasi Pembayaran</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal Diterbitkan</p>
              <p class="text-base font-medium text-black dark:text-white mt-1">
                {{ $invoice->issued_at ? \Carbon\Carbon::parse($invoice->issued_at)->format('d M Y, H:i') : '-' }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal Dibayar</p>
              <p class="text-base font-medium text-black dark:text-white mt-1">
                {{ $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('d M Y, H:i') : '-' }}
              </p>
            </div>
            @if($invoice->va_number)
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nomor Virtual Account</p>
                <p class="text-base font-mono font-medium text-black dark:text-white mt-1">{{ $invoice->va_number }}</p>
              </div>
            @endif
            @if($invoice->va_expiry_at)
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Kadaluarsa VA</p>
                <p class="text-base font-medium text-black dark:text-white mt-1">
                  {{ \Carbon\Carbon::parse($invoice->va_expiry_at)->format('d M Y, H:i') }}
                </p>
              </div>
            @endif
          </div>
        </div>

        <!-- Waste Delivery Info -->
        @if($invoice->wasteDelivery)
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Informasi Pengiriman Sampah</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">ID Delivery</p>
                <p class="text-base font-medium text-black dark:text-white mt-1">#{{ $invoice->waste_delivery_id }}</p>
              </div>
              @if($invoice->wasteDelivery->wasteDeliveryItems)
                <div class="md:col-span-2">
                  <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">Item Sampah</p>
                  <div class="space-y-2">
                    @foreach($invoice->wasteDelivery->wasteDeliveryItems as $item)
                      <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800/30 rounded-lg">
                        <span class="text-sm font-medium">{{ $item->waste?->name ?? 'N/A' }}</span>
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $item->quantity }} {{ $item->pocketSize?->name ?? 'kantong' }}</span>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif
            </div>
          </div>
        @endif

        <!-- Payment Transactions -->
        @if($invoice->payments && $invoice->payments->count() > 0)
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-semibold text-black dark:text-white mb-4">Riwayat Pembayaran</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800/50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ID Transaksi</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Jumlah</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Metode</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Waktu</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  @foreach($invoice->payments as $payment)
                    <tr>
                      <td class="px-4 py-3 text-sm font-medium text-black dark:text-white">
                        {{ $payment->midtrans_transaction_id ?? $payment->provider_transaction_id ?? '-' }}
                      </td>
                      <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                        Rp {{ number_format($payment->amount, 0, ',', '.') }}
                      </td>
                      <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                        {{ strtoupper(str_replace('_', ' ', $payment->payment_method ?? '-')) }}
                      </td>
                      <td class="px-4 py-3 text-sm">
                        @if(in_array($payment->status, ['settlement', 'success']))
                          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            {{ ucfirst($payment->status) }}
                          </span>
                        @elseif($payment->status === 'pending')
                          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Pending
                          </span>
                        @else
                          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            {{ ucfirst($payment->status) }}
                          </span>
                        @endif
                      </td>
                      <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                        {{ $payment->created_at->format('d M Y, H:i') }}
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
