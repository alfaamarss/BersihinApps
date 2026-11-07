<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'area_id',
        'rw_id',
        'name',
        'contact_phone',
        'address',
        'latitude',
        'longitude',
        'note',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function rw()
    {
        return $this->belongsTo(Rw::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'area_id', 'area_id');
    }

    public function wasteDeliveries()
    {
        return $this->hasMany(WasteDelivery::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get complaints related to this service account
     */
    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }
}
