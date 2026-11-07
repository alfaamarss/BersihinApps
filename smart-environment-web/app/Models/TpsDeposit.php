<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TpsDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'collector_id',
        'garbage_dump_id',
        'deposited_at',
        'latitude',
        'longitude',
        'notes',
    ];

    protected $casts = [
        'deposited_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Kolektor yang melakukan setor
     */
    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    /**
     * TPS tujuan
     */
    public function garbageDump()
    {
        return $this->belongsTo(GarbageDump::class, 'garbage_dump_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope untuk filter berdasarkan kolektor
     */
    public function scopeByCollector($query, $collectorId)
    {
        return $query->where('collector_id', $collectorId);
    }

    /**
     * Scope untuk filter berdasarkan TPS
     */
    public function scopeByTps($query, $garbageDumpId)
    {
        return $query->where('garbage_dump_id', $garbageDumpId);
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('deposited_at', $date);
    }

    /**
     * Scope untuk filter berdasarkan range tanggal
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('deposited_at', [$startDate, $endDate]);
    }
}
