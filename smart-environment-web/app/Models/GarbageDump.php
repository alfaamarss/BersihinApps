<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GarbageDump extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'kecamatan_id',
        'kelurahan_id',
        'rw_id',
        'address',
        'latitude',
        'longitude',
        'capacity_volume',
        'capacity_weight',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'capacity_volume' => 'float',
        'capacity_weight' => 'float',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Kecamatan (Area level Kecamatan)
     */
    public function kecamatan()
    {
        return $this->belongsTo(Area::class, 'kecamatan_id');
    }

    /**
     * Kelurahan (Area level Kelurahan)
     */
    public function kelurahan()
    {
        return $this->belongsTo(Area::class, 'kelurahan_id');
    }

    /**
     * RW
     */
    public function rw()
    {
        return $this->belongsTo(Rw::class);
    }

    /**
     * Deposits (log setor kolektor ke TPS ini)
     */
    public function deposits()
    {
        return $this->hasMany(TpsDeposit::class, 'garbage_dump_id');
    }

    /**
     * Collectors assigned to this TPS (many-to-many)
     */
    public function collectors()
    {
        return $this->belongsToMany(User::class, 'garbage_dump_collector', 'garbage_dump_id', 'collector_id')
            ->withTimestamps();
    }

    // ==================== SCOPES ====================

    /**
     * Scope untuk TPS aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope untuk TPS nonaktif
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'active' ? 'Aktif' : 'Nonaktif';
    }

    /**
     * Get full location string
     */
    public function getFullLocationAttribute(): string
    {
        $parts = [];
        
        if ($this->rw) {
            $parts[] = $this->rw->name;
        }
        if ($this->kelurahan) {
            $parts[] = $this->kelurahan->name;
        }
        if ($this->kecamatan) {
            $parts[] = $this->kecamatan->name;
        }
        
        return implode(', ', $parts) ?: '-';
    }

    /**
     * Check if TPS is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
