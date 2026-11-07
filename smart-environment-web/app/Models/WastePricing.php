<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WastePricing extends Model
{
    use HasFactory;

    protected $fillable = ['waste_id','pocket_size_id','price','effective_from','effective_to'];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'price' => 'decimal:2',
    ];

    public function waste()
    {
        return $this->belongsTo(Waste::class);
    }

    public function size()
    {
        return $this->belongsTo(PocketSize::class, 'pocket_size_id');
    }
}
