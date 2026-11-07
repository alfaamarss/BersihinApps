<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_method',
        'payment_type',
        'payment_channel',
        'order_id',
        'status',
        'snap_token',
        'payment_url',
        'qr_string',
        'va_number',
        'provider_transaction_id',
        'expired_at',
        'midtrans_response',
    ];

    protected $casts = [
        'amount' => 'float',
        'expired_at' => 'datetime',
        'midtrans_response' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
