@extends('layouts.master')

@section('title','Buat Tagihan')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="mb-6">
        <div class="flex items-center gap-3 mb-2">
          <x-button as="a" href="{{ route('admin.invoices.index') }}" variant="secondary" size="sm">
            ← Kembali
          </x-button>
        </div>
        <h2 class="text-2xl font-bold text-black dark:text-white">Buat Tagihan Baru</h2>
        <p class="text-sm text-gray-500 mt-1">Isi form untuk membuat tagihan baru.</p>
      </div>

      <form action="{{ route('admin.invoices.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Service Account -->
          <div>
            <label for="service_account_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Pelanggan <span class="text-red-500">*</span>
            </label>
            <select name="service_account_id" id="service_account_id" required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('service_account_id') border-red-500 @enderror">
              <option value="">Pilih Pelanggan</option>
              @foreach($serviceAccounts as $account)
                <option value="{{ $account->id }}" {{ old('service_account_id') == $account->id ? 'selected' : '' }}>
                  {{ $account->user?->name ?? $account->name }} - {{ $account->address }}
                </option>
              @endforeach
            </select>
            @error('service_account_id')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Waste Delivery (Optional) -->
          <div>
            <label for="waste_delivery_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Waste Delivery (Opsional)
            </label>
            <select name="waste_delivery_id" id="waste_delivery_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('waste_delivery_id') border-red-500 @enderror">
              <option value="">Tidak ada</option>
              @foreach($wasteDeliveries as $delivery)
                <option value="{{ $delivery->id }}" {{ old('waste_delivery_id') == $delivery->id ? 'selected' : '' }}>
                  Delivery #{{ $delivery->id }} - {{ $delivery->serviceAccount?->user?->name }}
                </option>
              @endforeach
            </select>
            @error('waste_delivery_id')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Invoice Number -->
          <div>
            <label for="invoice_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nomor Tagihan <span class="text-red-500">*</span>
            </label>
            <input type="text" name="invoice_number" id="invoice_number" value="{{ old('invoice_number', 'INV-' . date('YmdHis')) }}" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('invoice_number') border-red-500 @enderror"
                   placeholder="INV-20250101120000">
            @error('invoice_number')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Total Amount -->
          <div>
            <label for="total_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Total Tagihan (Rp) <span class="text-red-500">*</span>
            </label>
            <input type="number" name="total_amount" id="total_amount" value="{{ old('total_amount', 0) }}" required min="0" step="1"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('total_amount') border-red-500 @enderror"
                   placeholder="50000">
            @error('total_amount')
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
              <option value="not_paid" {{ old('status') == 'not_paid' ? 'selected' : '' }}>Belum Dibayar</option>
              <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>Sudah Dibayar</option>
              <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
            @error('status')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Issued At -->
          <div>
            <label for="issued_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Tanggal Diterbitkan <span class="text-red-500">*</span>
            </label>
            <input type="datetime-local" name="issued_at" id="issued_at" value="{{ old('issued_at', now()->format('Y-m-d\TH:i')) }}" required
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('issued_at') border-red-500 @enderror">
            @error('issued_at')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- Paid At (Optional) -->
          <div>
            <label for="paid_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Tanggal Dibayar (Opsional)
            </label>
            <input type="datetime-local" name="paid_at" id="paid_at" value="{{ old('paid_at') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('paid_at') border-red-500 @enderror">
            @error('paid_at')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- VA Number (Optional) -->
          <div>
            <label for="va_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Nomor Virtual Account (Opsional)
            </label>
            <input type="text" name="va_number" id="va_number" value="{{ old('va_number') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('va_number') border-red-500 @enderror"
                   placeholder="8808012345678901">
            @error('va_number')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>

          <!-- VA Expiry (Optional) -->
          <div>
            <label for="va_expiry_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Kadaluarsa VA (Opsional)
            </label>
            <input type="datetime-local" name="va_expiry_at" id="va_expiry_at" value="{{ old('va_expiry_at') }}"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white @error('va_expiry_at') border-red-500 @enderror">
            @error('va_expiry_at')
              <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-6 border-t border-gray-200 dark:border-gray-700">
          <x-button as="a" href="{{ route('admin.invoices.index') }}" variant="secondary" size="md">Batal</x-button>
          <x-button type="submit" variant="primary" size="md">Simpan Tagihan</x-button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
