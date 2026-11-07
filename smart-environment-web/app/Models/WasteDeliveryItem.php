<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasteDeliveryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'waste_delivery_id','waste_id','pocket_size_id','quantity','unit_price','total_price'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'float',
        'total_price' => 'float',
    ];

    public function wasteDelivery()
    {
        return $this->belongsTo(WasteDelivery::class);
    }

    public function waste()
    {
        return $this->belongsTo(Waste::class);
    }

    public function pocketSize()
    {
        return $this->belongsTo(PocketSize::class);
    }
}
