<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'waste_delivery_id',
        'service_account_id',
        'invoice_number',
        'va_number',
        'va_expiry_at',
        'snap_token',
        'payment_type',
        'payment_channel',
        'total_amount',
        'status',
        'issued_at',
        'paid_at'
    ];

    protected $casts = [
        'total_amount' => 'float',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
        'va_expiry_at' => 'datetime',
    ];

    public function serviceAccount()
    {
        return $this->belongsTo(ServiceAccount::class);
    }

    public function wasteDelivery()
    {
        return $this->belongsTo(WasteDelivery::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
