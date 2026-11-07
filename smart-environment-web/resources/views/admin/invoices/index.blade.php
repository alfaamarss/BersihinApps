@extends('layouts.master')

@section('title','Tagihan')

@section('content')
<div class="grid grid-cols-12 gap-4 md:gap-6">
  <div class="col-span-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
          <h2 class="text-2xl font-bold text-black dark:text-white">Tagihan</h2>
          <p class="text-sm text-gray-500 mt-1">Kelola tagihan pembayaran pelanggan.</p>
        </div>
        <!-- <div class="flex items-center gap-3"> -->
          <!-- <x-button as="a" href="{{ route('admin.invoices.create') }}" variant="primary" size="md">Buat Tagihan</x-button> -->
        <!-- </div> -->
      </div>

      <!-- Filter Section -->
      <div class="mb-4">
        <form method="GET" action="{{ route('admin.invoices.index') }}" class="flex flex-wrap gap-3">
          <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Cari nama pelanggan atau service account..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
          </div>
          <div class="min-w-[150px]">
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
              <option value="">Semua Status</option>
              <option value="not_paid" {{ request('status') == 'not_paid' ? 'selected' : '' }}>Belum Dibayar</option>
              <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Sudah Dibayar</option>
              <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
          </div>
          <!-- <div class="min-w-[150px]">
            <select name="service_account_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
              <option value="">Semua Pelanggan</option>
              @foreach($serviceAccounts ?? [] as $account)
                <option value="{{ $account->id }}" {{ request('service_account_id') == $account->id ? 'selected' : '' }}>
                  {{ $account->user?->name ?? $account->name }}
                </option>
              @endforeach
            </select>
          </div> -->
          <!-- <div class="min-w-[140px]">
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   placeholder="Dari Tanggal"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
          </div>
          <div class="min-w-[140px]">
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   placeholder="Sampai Tanggal"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white">
          </div> -->
          <x-button type="submit" variant="secondary" size="md">Filter</x-button>
          @if(request()->hasAny(['search', 'status', 'service_account_id', 'date_from', 'date_to']))
            <x-button as="a" href="{{ route('admin.invoices.index') }}" variant="secondary" size="md">Reset</x-button>
          @endif
        </form>
      </div>

      <x-table :headers="[
          ['label' => 'Nomor Tagihan','class'=>'min-w-[160px]'],
          ['label' => 'Pelanggan','class'=>'min-w-[160px]'],
          ['label' => 'Total','class'=>'min-w-[120px]'],
          ['label' => 'Status','class'=>'min-w-[120px]'],
          ['label' => 'Diterbitkan','class'=>'min-w-[120px]'],
          ['label' => 'Dibayar','class'=>'min-w-[120px]'],
          ['label' => 'Actions']
      ]" empty="Tidak ada data tagihan.">
        @foreach($invoices ?? [] as $invoice)
          <x-table.row>
            <x-table.cell>
              <div class="font-medium">{{ $invoice->invoice_number }}</div>
              @if($invoice->wasteDelivery)
                <div class="text-xs text-gray-500 mt-1">
                  Delivery #{{ $invoice->waste_delivery_id }}
                </div>
              @endif
            </x-table.cell>
            <x-table.cell>
              @if($invoice->serviceAccount)
                <div class="font-medium text-gray-900 dark:text-white">
                  {{ $invoice->serviceAccount->user?->name ?? '-' }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                  {{ $invoice->serviceAccount->name }}
                </div>
              @else
                <div class="text-gray-400">-</div>
              @endif
            </x-table.cell>
            <x-table.cell>
              <div class="font-semibold text-blue-600">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</div>
            </x-table.cell>
            <x-table.cell>
              @if($invoice->status === 'paid')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                  Lunas
                </span>
              @elseif($invoice->status === 'not_paid')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                  Belum Bayar
                </span>
              @else
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                  Batal
                </span>
              @endif
            </x-table.cell>
            <x-table.cell muted>
              {{ $invoice->issued_at ? \Carbon\Carbon::parse($invoice->issued_at)->format('d M Y') : '-' }}
              @if($invoice->issued_at)
                <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($invoice->issued_at)->format('H:i') }}</div>
              @endif
            </x-table.cell>
            <x-table.cell muted>
              {{ $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('d M Y') : '-' }}
              @if($invoice->paid_at)
                <div class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($invoice->paid_at)->format('H:i') }}</div>
              @endif
            </x-table.cell>
            <x-table.cell>
              <div class="flex items-center gap-2">
                <x-button as="a" href="{{ route('admin.invoices.show', $invoice->id) }}" variant="secondary" size="sm">Detail</x-button>
                <!-- <x-button as="a" href="{{ route('admin.invoices.edit', $invoice->id) }}" variant="secondary" size="sm">Edit</x-button> -->
                <form action="{{ route('admin.invoices.destroy', $invoice->id) }}" method="POST" class="inline delete-form">
                  @csrf
                  @method('DELETE')
                  <x-button type="submit" variant="danger" size="sm">Hapus</x-button>
                </form>
              </div>
            </x-table.cell>
          </x-table.row>
        @endforeach
      </x-table>

      <!-- Pagination -->
      @if(isset($invoices) && $invoices->hasPages())
        <div class="mt-6">
          {{ $invoices->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('body:end')
<script>
  document.querySelectorAll('.delete-form').forEach(form => {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      Swal.fire({
        title: 'Hapus Tagihan?',
        text: 'Data tagihan akan dihapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          form.submit();
        }
      });
    });
  });
</script>
@endpush
