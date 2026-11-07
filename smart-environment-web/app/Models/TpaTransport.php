<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TpaTransport extends Model
{
    use HasFactory;

    protected $fillable = [
        'garbage_dump_id',
        'collector_id',
        'transported_at',
        'notes',
    ];

    protected $casts = [
        'transported_at' => 'datetime',
    ];

    public function garbageDump()
    {
        return $this->belongsTo(GarbageDump::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }
}
