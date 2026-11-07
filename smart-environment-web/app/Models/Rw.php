<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rw extends Model
{
    use HasFactory;

    protected $fillable = ['kelurahan_id', 'name'];

    /**
     * Get the kelurahan (area) that this RW belongs to
     */
    public function kelurahan()
    {
        return $this->belongsTo(Area::class, 'kelurahan_id');
    }

    /**
     * Get all service accounts in this RW
     */
    public function serviceAccounts()
    {
        return $this->hasMany(ServiceAccount::class);
    }

    /**
     * Get all collector assignments for this RW
     */
    public function collectorAssignments()
    {
        return $this->hasMany(CollectorAssignment::class);
    }
}
