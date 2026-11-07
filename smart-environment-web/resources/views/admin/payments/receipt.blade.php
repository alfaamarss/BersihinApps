<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            font-size: 12px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #2563eb;
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            color: #666;
            font-size: 12px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            margin: 20px 0;
        }
        .status-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 40%;
            padding: 8px 10px;
            color: #666;
            font-weight: 500;
        }
        .info-value {
            display: table-cell;
            padding: 8px 10px;
            color: #000;
            font-weight: 600;
        }
        .info-row:nth-child(even) {
            background-color: #f9fafb;
        }
        .amount-box {
            background-color: #dbeafe;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin: 20px 0;
        }
        .amount-label {
            color: #1e40af;
            font-size: 12px;
            margin-bottom: 5px;
        }
        .amount-value {
            color: #1e3a8a;
            font-size: 24px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10px;
        }
        .transaction-id {
            font-family: 'Courier New', monospace;
            background-color: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>BUKTI PEMBAYARAN</h1>
        <p>{{ $appSetting?->app_name ?? 'Smart Environment' }} - Bukti Pembayaran Tagihan Sampah</p>
    </div>

    <div class="status-badge status-success">
        PEMBAYARAN BERHASIL
    </div>

    <div class="section">
        <div class="section-title">Informasi Transaksi</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Transaction ID</div>
                <div class="info-value">
                    <span class="transaction-id">{{ $payment->midtrans_transaction_id ?? $payment->provider_transaction_id ?? '-' }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">Tanggal Transaksi</div>
                <div class="info-value">{{ $payment->created_at->format('d F Y, H:i:s') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Status</div>
                <div class="info-value">{{ strtoupper($payment->status) }}</div>
            </div>
        </div>
    </div>

    <div class="amount-box">
        <div class="amount-label">JUMLAH DIBAYAR</div>
        <div class="amount-value">Rp {{ number_format($payment->amount, 0, ',', '.') }}</div>
    </div>

    <div class="section">
        <div class="section-title">Detail Pembayaran</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Metode Pembayaran</div>
                <div class="info-value">{{ strtoupper(str_replace('_', ' ', $payment->payment_method ?? '-')) }}</div>
            </div>
            @if($payment->payment_type)
            <div class="info-row">
                <div class="info-label">Tipe Pembayaran</div>
                <div class="info-value">{{ ucfirst($payment->payment_type) }}</div>
            </div>
            @endif
            @if($payment->payment_channel)
            <div class="info-row">
                <div class="info-label">Channel</div>
                <div class="info-value">{{ strtoupper($payment->payment_channel) }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($payment->invoice)
    <div class="section">
        <div class="section-title">Informasi Tagihan</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nomor Tagihan</div>
                <div class="info-value">{{ $payment->invoice->invoice_number }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Service Account</div>
                <div class="info-value">{{ $payment->invoice->serviceAccount?->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Nama Pelanggan</div>
                <div class="info-value">{{ $payment->invoice->serviceAccount?->user?->name ?? '-' }}</div>
            </div>
            @if($payment->invoice->serviceAccount?->address)
            <div class="info-row">
                <div class="info-label">Alamat</div>
                <div class="info-value">{{ $payment->invoice->serviceAccount->address }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Total Tagihan</div>
                <div class="info-value">Rp {{ number_format($payment->invoice->total_amount, 0, ',', '.') }}</div>
            </div>
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Dokumen ini adalah bukti pembayaran yang sah dan dihasilkan secara otomatis oleh sistem.</p>
        <p>Untuk pertanyaan lebih lanjut, silakan hubungi customer service kami.</p>
        <p style="margin-top: 10px;">Dicetak pada: {{ now()->format('d F Y, H:i:s') }}</p>
    </div>
</body>
</html>
