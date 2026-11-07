<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CollectorAssignment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','schedule_id','rw_id','start_date','end_date'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function rw()
    {
        return $this->belongsTo(Rw::class);
    }

    // Helper untuk akses area melalui schedule
    public function getAreaAttribute()
    {
        return $this->schedule?->area;
    }

    /**
     * Check if collector has conflicting assignment on same day/area/RW
     * Only prevents duplicate assignments to the SAME area and RW on the same day
     * Allows collector to have multiple assignments to:
     * - DIFFERENT areas
     * - DIFFERENT RWs within the same area
     * 
     * @param int $userId
     * @param int $scheduleId
     * @param string $startDate
     * @param string|null $endDate
     * @param int|null $excludeId
     * @param int|null $rwId
     * @return array|null Array with conflict details if found, null otherwise
     */
    public static function checkConflict($userId, $scheduleId, $startDate, $endDate = null, $excludeId = null, $rwId = null)
    {
        // Get the schedule details to check day_of_week and area
        $newSchedule = Schedule::with('area.parent')->findOrFail($scheduleId);
        $newDayOfWeek = $newSchedule->day_of_week;
        $newAreaId = $newSchedule->area_id;
        
        $startDate = Carbon::parse($startDate);
        $endDate = $endDate ? Carbon::parse($endDate) : null;
        
        // Find existing assignments for this collector on the same day and same area
        $query = self::where('user_id', $userId)
            ->whereHas('schedule', function($q) use ($newDayOfWeek, $newAreaId) {
                $q->where('day_of_week', $newDayOfWeek)
                  ->where('area_id', $newAreaId);
            })
            ->with(['schedule.area', 'rw']);
        
        // Enhanced RW conflict logic:
        // - If assigning to entire kelurahan (rw_id = null), conflict with ANY assignment in that kelurahan
        // - If assigning to specific RW, conflict with same RW OR entire kelurahan assignment
        if ($rwId === null) {
            // New assignment covers entire kelurahan - conflicts with ANY existing assignment
            // (No additional filter needed - we already filter by area)
        } else {
            // New assignment is for specific RW - conflicts with:
            // 1. Same specific RW
            // 2. Entire kelurahan assignment (rw_id = null)
            $query->where(function($q) use ($rwId) {
                $q->where('rw_id', $rwId)  // Same RW
                  ->orWhereNull('rw_id');   // Or entire kelurahan
            });
        }
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $existingAssignments = $query->get();
        
        foreach ($existingAssignments as $existing) {
            // Check date overlap
            $existingStart = Carbon::parse($existing->start_date);
            $existingEnd = $existing->end_date ? Carbon::parse($existing->end_date) : null;
            
            // Check if date ranges overlap
            $hasOverlap = false;
            
            if ($existingEnd === null && $endDate === null) {
                // Both are ongoing
                $hasOverlap = true;
            } elseif ($existingEnd === null) {
                // Existing is ongoing, new has end date
                $hasOverlap = $existingStart->lte($endDate);
            } elseif ($endDate === null) {
                // New is ongoing, existing has end date
                $hasOverlap = $startDate->lte($existingEnd);
            } else {
                // Both have end dates
                $hasOverlap = $startDate->lte($existingEnd) && $endDate->gte($existingStart);
            }
            
            if ($hasOverlap) {
                $rwInfo = $existing->rw ? 'RW ' . $existing->rw->name : 'Seluruh RW';
                
                return [
                    'conflict' => true,
                    'existing_assignment' => $existing,
                    'collector_name' => $existing->user->name,
                    'day' => $newDayOfWeek,
                    'existing_area' => $existing->schedule->area->name,
                    'new_area' => $newSchedule->area->name,
                    'rw_info' => $rwInfo,
                    'is_same_area' => true, // Always true now since we only check same area
                    'existing_start' => $existing->start_date->format('d M Y'),
                    'existing_end' => $existing->end_date ? $existing->end_date->format('d M Y') : 'Tidak terbatas',
                ];
            }
        }
        
        return null;
    }

    /**
     * Check if collector is already assigned to a different kecamatan on the same day
     * A collector can only be assigned to kelurahans within the SAME kecamatan on a given day
     * 
     * @param int $userId
     * @param int $scheduleId
     * @param string $startDate
     * @param string|null $endDate
     * @param int|null $excludeId
     * @return array|null Array with conflict details if found, null otherwise
     */
    public static function checkKecamatanConflict($userId, $scheduleId, $startDate, $endDate = null, $excludeId = null)
    {
        // Get the schedule details to check day_of_week and kecamatan
        $newSchedule = Schedule::with('area.parent')->findOrFail($scheduleId);
        $newDayOfWeek = $newSchedule->day_of_week;
        $newKecamatanId = $newSchedule->area->parent_id; // parent of kelurahan = kecamatan
        
        if (!$newKecamatanId) {
            return null; // No parent kecamatan, skip check
        }
        
        $startDate = Carbon::parse($startDate);
        $endDate = $endDate ? Carbon::parse($endDate) : null;
        
        // Find existing assignments for this collector on the same day but DIFFERENT kecamatan
        $query = self::where('user_id', $userId)
            ->whereHas('schedule', function($q) use ($newDayOfWeek) {
                $q->where('day_of_week', $newDayOfWeek);
            })
            ->whereHas('schedule.area', function($q) use ($newKecamatanId) {
                $q->where('parent_id', '!=', $newKecamatanId);
            })
            ->with(['schedule.area.parent', 'user']);
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $existingAssignments = $query->get();
        
        foreach ($existingAssignments as $existing) {
            // Check date overlap
            $existingStart = Carbon::parse($existing->start_date);
            $existingEnd = $existing->end_date ? Carbon::parse($existing->end_date) : null;
            
            // Check if date ranges overlap
            $hasOverlap = false;
            
            if ($existingEnd === null && $endDate === null) {
                // Both are ongoing
                $hasOverlap = true;
            } elseif ($existingEnd === null) {
                // Existing is ongoing, new has end date
                $hasOverlap = $existingStart->lte($endDate);
            } elseif ($endDate === null) {
                // New is ongoing, existing has end date
                $hasOverlap = $startDate->lte($existingEnd);
            } else {
                // Both have end dates
                $hasOverlap = $startDate->lte($existingEnd) && $endDate->gte($existingStart);
            }
            
            if ($hasOverlap) {
                $existingKecamatan = $existing->schedule->area->parent;
                $newKecamatan = $newSchedule->area->parent;
                
                return [
                    'conflict' => true,
                    'existing_assignment' => $existing,
                    'collector_name' => $existing->user->name,
                    'day' => $newDayOfWeek,
                    'existing_kecamatan' => $existingKecamatan ? $existingKecamatan->name : '-',
                    'existing_kelurahan' => $existing->schedule->area->name,
                    'new_kecamatan' => $newKecamatan ? $newKecamatan->name : '-',
                    'new_kelurahan' => $newSchedule->area->name,
                    'existing_start' => $existing->start_date->format('d M Y'),
                    'existing_end' => $existing->end_date ? $existing->end_date->format('d M Y') : 'Tidak terbatas',
                ];
            }
        }
        
        return null;
    }

    /**
     * Get assigned RWs for a kelurahan on a specific day with overlapping dates
     * Used to disable already-assigned RWs in the dropdown
     * 
     * @param int $kelurahanId
     * @param string $dayOfWeek
     * @param string $startDate
     * @param string|null $endDate
     * @param int|null $excludeAssignmentId
     * @return array Array of assigned RW IDs
     */
    public static function getAssignedRws($kelurahanId, $dayOfWeek, $startDate, $endDate = null, $excludeAssignmentId = null)
    {
        $startDate = Carbon::parse($startDate);
        $endDate = $endDate ? Carbon::parse($endDate) : null;
        
        // Find assignments for this kelurahan on this day
        $query = self::whereHas('schedule', function($q) use ($kelurahanId, $dayOfWeek) {
                $q->where('area_id', $kelurahanId)
                  ->where('day_of_week', $dayOfWeek);
            })
            ->whereNotNull('rw_id') // Only specific RW assignments
            ->with(['rw', 'user']);
            
        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }
        
        $assignments = $query->get();
        $assignedRws = [];
        
        foreach ($assignments as $existing) {
            // Check date overlap
            $existingStart = Carbon::parse($existing->start_date);
            $existingEnd = $existing->end_date ? Carbon::parse($existing->end_date) : null;
            
            // Check if date ranges overlap
            $hasOverlap = false;
            
            if ($existingEnd === null && $endDate === null) {
                $hasOverlap = true;
            } elseif ($existingEnd === null) {
                $hasOverlap = $existingStart->lte($endDate);
            } elseif ($endDate === null) {
                $hasOverlap = $startDate->lte($existingEnd);
            } else {
                $hasOverlap = $startDate->lte($existingEnd) && $endDate->gte($existingStart);
            }
            
            if ($hasOverlap && $existing->rw_id) {
                $assignedRws[] = [
                    'rw_id' => $existing->rw_id,
                    'rw_name' => $existing->rw ? $existing->rw->name : '-',
                    'collector_name' => $existing->user ? $existing->user->name : '-',
                ];
            }
        }
        
        return $assignedRws;
    }

    /**
     * Check if entire kelurahan is already assigned to a collector on specific day
     * (meaning rw_id is null = covers all RWs)
     * 
     * @param int $kelurahanId
     * @param string $dayOfWeek
     * @param string $startDate
     * @param string|null $endDate
     * @param int|null $excludeAssignmentId
     * @return array|null
     */
    public static function checkEntireKelurahanAssigned($kelurahanId, $dayOfWeek, $startDate, $endDate = null, $excludeAssignmentId = null)
    {
        $startDate = Carbon::parse($startDate);
        $endDate = $endDate ? Carbon::parse($endDate) : null;
        
        // Find assignments for entire kelurahan (rw_id = null)
        $query = self::whereHas('schedule', function($q) use ($kelurahanId, $dayOfWeek) {
                $q->where('area_id', $kelurahanId)
                  ->where('day_of_week', $dayOfWeek);
            })
            ->whereNull('rw_id') // Entire kelurahan assignment
            ->with(['user']);
            
        if ($excludeAssignmentId) {
            $query->where('id', '!=', $excludeAssignmentId);
        }
        
        $assignments = $query->get();
        
        foreach ($assignments as $existing) {
            // Check date overlap
            $existingStart = Carbon::parse($existing->start_date);
            $existingEnd = $existing->end_date ? Carbon::parse($existing->end_date) : null;
            
            // Check if date ranges overlap
            $hasOverlap = false;
            
            if ($existingEnd === null && $endDate === null) {
                $hasOverlap = true;
            } elseif ($existingEnd === null) {
                $hasOverlap = $existingStart->lte($endDate);
            } elseif ($endDate === null) {
                $hasOverlap = $startDate->lte($existingEnd);
            } else {
                $hasOverlap = $startDate->lte($existingEnd) && $endDate->gte($existingStart);
            }
            
            if ($hasOverlap) {
                return [
                    'assigned' => true,
                    'collector_name' => $existing->user ? $existing->user->name : '-',
                    'existing_start' => $existing->start_date->format('d M Y'),
                    'existing_end' => $existing->end_date ? $existing->end_date->format('d M Y') : 'Tidak terbatas',
                ];
            }
        }
        
        return null;
    }

    /**
     * Check if another collector is already assigned to the same area/RW on the same day
     * Prevents multiple collectors being assigned to the same area/RW
     * 
     * @param int $scheduleId
     * @param string $startDate
     * @param string|null $endDate
     * @param int|null $excludeId
     * @param int|null $rwId
     * @return array|null Array with conflict details if found, null otherwise
     */
    public static function checkAreaRwConflict($scheduleId, $startDate, $endDate = null, $excludeId = null, $rwId = null)
    {
        // Get the schedule details to check day_of_week and area
        $newSchedule = Schedule::with('area')->findOrFail($scheduleId);
        $newDayOfWeek = $newSchedule->day_of_week;
        $newAreaId = $newSchedule->area_id;
        
        $startDate = Carbon::parse($startDate);
        $endDate = $endDate ? Carbon::parse($endDate) : null;
        
        // Find ANY assignment (any collector) on the same day and same area
        $query = self::whereHas('schedule', function($q) use ($newDayOfWeek, $newAreaId) {
                $q->where('day_of_week', $newDayOfWeek)
                  ->where('area_id', $newAreaId);
            })
            ->with(['schedule.area', 'rw', 'user']);
        
        // Enhanced RW conflict logic:
        // - If assigning to entire kelurahan (rw_id = null), conflict with ANY assignment in that kelurahan
        // - If assigning to specific RW, conflict with same RW OR entire kelurahan assignment
        if ($rwId === null) {
            // New assignment covers entire kelurahan - conflicts with ANY existing assignment
            // (No additional filter needed - we already filter by area)
        } else {
            // New assignment is for specific RW - conflicts with:
            // 1. Same specific RW
            // 2. Entire kelurahan assignment (rw_id = null)
            $query->where(function($q) use ($rwId) {
                $q->where('rw_id', $rwId)  // Same RW
                  ->orWhereNull('rw_id');   // Or entire kelurahan
            });
        }
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $existingAssignments = $query->get();
        
        foreach ($existingAssignments as $existing) {
            // Check date overlap
            $existingStart = Carbon::parse($existing->start_date);
            $existingEnd = $existing->end_date ? Carbon::parse($existing->end_date) : null;
            
            // Check if date ranges overlap
            $hasOverlap = false;
            
            if ($existingEnd === null && $endDate === null) {
                // Both are ongoing
                $hasOverlap = true;
            } elseif ($existingEnd === null) {
                // Existing is ongoing, new has end date
                $hasOverlap = $existingStart->lte($endDate);
            } elseif ($endDate === null) {
                // New is ongoing, existing has end date
                $hasOverlap = $startDate->lte($existingEnd);
            } else {
                // Both have end dates
                $hasOverlap = $startDate->lte($existingEnd) && $endDate->gte($existingStart);
            }
            
            if ($hasOverlap) {
                $rwInfo = $existing->rw ? $existing->rw->name : 'Seluruh RW';
                
                return [
                    'conflict' => true,
                    'existing_assignment' => $existing,
                    'collector_name' => $existing->user->name,
                    'collector_email' => $existing->user->email,
                    'day' => $newDayOfWeek,
                    'area' => $existing->schedule->area->name,
                    'rw_info' => $rwInfo,
                    'existing_start' => $existing->start_date->format('d M Y'),
                    'existing_end' => $existing->end_date ? $existing->end_date->format('d M Y') : 'Tidak terbatas',
                ];
            }
        }
        
        return null;
    }
}
