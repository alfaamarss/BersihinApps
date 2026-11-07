<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Waste extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['code', 'category', 'name', 'description'];
    
    public function deliveryItems()
    {
        return $this->hasMany(WasteDeliveryItem::class);
    }
}
