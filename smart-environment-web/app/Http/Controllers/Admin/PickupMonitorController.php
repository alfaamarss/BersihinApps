<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WasteDelivery;
use App\Models\User;
use App\Models\Area;
use App\Services\WasteDeliveryGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PickupMonitorController extends Controller
{
    /**
     * Display the pickup monitoring dashboard
     */
    public function index()
    {
        // Get Kecamatan (parent areas)
        $kecamatans = Area::whereNull('parent_id')->orderBy('name')->get();
        
        $collectors = User::whereHas('roles', function($q) {
            $q->where('name', 'collector');
        })->orderBy('name')->get();

        return view('admin.pickups.monitor', compact('kecamatans', 'collectors'));
    }
    
    /**
     * Get Kelurahan by Kecamatan
     */
    public function getKelurahan(Request $request)
    {
        $kecamatanId = $request->kecamatan_id;
        
        if (!$kecamatanId) {
            return response()->json(['data' => []]);
        }
        
        $kelurahans = Area::where('parent_id', $kecamatanId)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return response()->json(['data' => $kelurahans]);
    }
    
    /**
     * Generate waste deliveries
     */
    public function generateDeliveries(Request $request, WasteDeliveryGeneratorService $generatorService)
    {
        try {
            $daysAhead = $request->input('days_ahead', 7); // Default 7 hari ke depan
            
            $summary = $generatorService->generateAllSchedules($daysAhead);
            
            $message = "Berhasil generate deliveries: {$summary['deliveries_created']} deliveries dibuat";
            
            if (count($summary['errors']) > 0) {
                $message .= ", dengan " . count($summary['errors']) . " error";
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $summary
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generating deliveries: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal generate deliveries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pickup data for monitoring (API endpoint)
     *
     * GET /api/v1/admin/pickups/monitor
     */
    public function monitor(Request $request)
    {
        $query = WasteDelivery::query()
            ->with([
                'serviceAccount.user',
                'serviceAccount.area',
                'schedule.collectorAssignments.user',
                'assignedCollector',
                'wasteDeliveryItems.waste',
                'wasteDeliveryItems.pocketSize',
                'invoice'
            ]);
        
        // Get the date filter
        $date = $request->has('date') ? Carbon::parse($request->date) : today();
        
        // Include both scheduled pickups and off-schedule requests filtered by date
        $query->where(function($q) use ($date) {
            // Scheduled pickups based on date
            $q->where(function($sub) use ($date) {
                $sub->where('pickup_type', 'scheduled')
                    ->whereDate('pickup_date', $date);
            });
            
            // Off-schedule requests - filter by requested_pickup_date or pickup_date
            $q->orWhere(function($sub) use ($date) {
                $sub->where('pickup_type', 'request')
                    ->where('request_status', '!=', 'sent')
                    ->where(function($dateFilter) use ($date) {
                        // Match by pickup_date or requested_pickup_date
                        $dateFilter->whereDate('pickup_date', $date)
                            ->orWhereDate('requested_pickup_date', $date);
                    });
            });
        });

        // Filter by date range
        if ($request->has('date_from') && $request->has('date_to')) {
            $query->whereBetween('pickup_date', [
                Carbon::parse($request->date_from),
                Carbon::parse($request->date_to)
            ]);
        }

        // Filter by status
        if ($request->filled('status') && $request->status !== 'all' && $request->status !== '' && $request->status !== null) {
            $query->where('status', $request->status);
        }

        // Filter by confirmation status
        if ($request->filled('confirmation_status') && $request->confirmation_status !== 'all' && $request->confirmation_status !== '' && $request->confirmation_status !== null) {
            $query->where('confirmation_status', $request->confirmation_status);
        }

        // Filter by area
        if ($request->filled('area_id') && $request->area_id !== 'all' && $request->area_id !== '' && $request->area_id !== null) {
            $query->whereHas('serviceAccount', function($q) use ($request) {
                $q->where('area_id', $request->area_id);
            });
        }

        // Filter by collector (support both scheduled and off-schedule)
        if ($request->filled('collector_id') && $request->collector_id !== 'all' && $request->collector_id !== '' && $request->collector_id !== null) {
            $query->where(function($q) use ($request) {
                // For scheduled pickups
                $q->whereHas('schedule.collectorAssignments', function($sub) use ($request) {
                    $sub->where('user_id', $request->collector_id)
                        ->where('start_date', '<=', now())
                        ->where(function($dateQ) {
                            $dateQ->whereNull('end_date')
                                  ->orWhere('end_date', '>=', now());
                        });
                })
                // For off-schedule pickups
                ->orWhere('assigned_collector_id', $request->collector_id);
            });
        }

        // Search by resident name, service account name, or address
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('serviceAccount', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sort - default terbaru di atas (descending by id)
        $sortBy = $request->get('sort_by', 'id');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->get('per_page', 15);
        $pickups = $query->paginate($perPage);        // Transform data for frontend
        $pickups->getCollection()->transform(function($pickup) {
            // Determine the appropriate date to display
            $displayDate = $pickup->pickup_type === 'request' 
                ? ($pickup->requested_pickup_date ? $pickup->requested_pickup_date->format('Y-m-d') : null)
                : ($pickup->pickup_date ? $pickup->pickup_date->format('Y-m-d') : null);
                
            return [
                'id' => $pickup->id,
                'pickup_type' => $pickup->pickup_type,
                'request_status' => $pickup->request_status,
                'pickup_date' => $displayDate,
                'scheduled_at' => $pickup->scheduled_at ? $pickup->scheduled_at->format('Y-m-d H:i') : null,
                'requested_pickup_date' => $pickup->requested_pickup_date ? $pickup->requested_pickup_date->format('Y-m-d') : null,
                'requested_pickup_time' => $pickup->requested_pickup_time,
                'status' => $pickup->status,
                'confirmation_status' => $pickup->confirmation_status,
                'resident' => [
                    'name' => optional(optional($pickup->serviceAccount)->user)->name ?? '-',
                    'account_number' => optional($pickup->serviceAccount)->account_number ?? '-',
                    'address' => optional(optional($pickup->serviceAccount)->user)->address ?? '-',
                ],
                'service_account' => [
                    'name' => optional($pickup->serviceAccount)->name ?? '-',
                    'address' => optional($pickup->serviceAccount)->address ?? '-',
                    'user' => [
                        'name' => optional(optional($pickup->serviceAccount)->user)->name ?? '-',
                    ],
                ],
                'area' => [
                    'id' => optional(optional($pickup->serviceAccount)->area)->id ?? null,
                    'name' => optional(optional($pickup->serviceAccount)->area)->name ?? '-',
                ],
                'collector' => $this->getCollectorInfo($pickup),
                'waste_items' => $pickup->wasteDeliveryItems->map(function($item) {
                    return [
                        'waste_name' => optional($item->waste)->category ?? '-',
                        'pocket_size' => optional($item->pocketSize)->name ?? '-',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                    ];
                }),
                'total_amount' => $pickup->total_amount ?? 0,
                'extra_fee' => $pickup->extra_fee ?? 0,
                'photo_url' => $pickup->photo_url ? Storage::url($pickup->photo_url) : null,
                'confirmed_at' => $pickup->confirmed_at ? $pickup->confirmed_at->format('Y-m-d H:i') : null,
                'collected_at' => $pickup->collected_at ? $pickup->collected_at->format('Y-m-d H:i') : null,
                'completed_at' => $pickup->completed_at ? $pickup->completed_at->format('Y-m-d H:i') : null,
                'resident_note' => $pickup->resident_note,
                'collector_notes' => $pickup->collector_notes,
                'invoice' => $pickup->invoice ? [
                    'invoice_number' => $pickup->invoice->invoice_number,
                    'status' => $pickup->invoice->status,
                    'total_amount' => $pickup->invoice->total_amount,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $pickups->items(),
            'pagination' => [
                'current_page' => $pickups->currentPage(),
                'per_page' => $pickups->perPage(),
                'total' => $pickups->total(),
                'last_page' => $pickups->lastPage(),
                'from' => $pickups->firstItem(),
                'to' => $pickups->lastItem(),
            ]
        ]);
    }

    /**
     * Get statistics for dashboard
     *
     * GET /api/v1/admin/pickups/statistics
     */
    public function statistics(Request $request)
    {
        $date = $request->has('date')
            ? Carbon::parse($request->date)
            : today();

        // Include both scheduled pickups (by date) and request pickups filtered by date
        // This should match the same logic as monitor() method for consistency
        $query = WasteDelivery::where(function($q) use ($date) {
            // Scheduled pickups based on date
            $q->where(function($sub) use ($date) {
                $sub->where('pickup_type', 'scheduled')
                    ->whereDate('pickup_date', $date);
            });
            
            // Off-schedule/request pickups - filter by requested_pickup_date or pickup_date
            $q->orWhere(function($sub) use ($date) {
                $sub->where('pickup_type', 'request')
                    ->where('request_status', '!=', 'sent')
                    ->where(function($dateFilter) use ($date) {
                        // Match by pickup_date or requested_pickup_date
                        $dateFilter->whereDate('pickup_date', $date)
                            ->orWhereDate('requested_pickup_date', $date);
                    });
            });
        });

        // Apply filters if provided (use filled() instead of has() to check for non-empty values)
        if ($request->filled('area_id') && $request->area_id != 'all') {
            $query->whereHas('serviceAccount', function($q) use ($request) {
                $q->where('area_id', $request->area_id);
            });
        }

        if ($request->filled('collector_id') && $request->collector_id != 'all') {
            $query->where(function($q) use ($request) {
                // For scheduled pickups
                $q->whereHas('schedule.collectorAssignments', function($sub) use ($request) {
                    $sub->where('user_id', $request->collector_id)
                        ->where('start_date', '<=', now())
                        ->where(function($dateQ) {
                            $dateQ->whereNull('end_date')
                                  ->orWhere('end_date', '>=', now());
                        });
                })
                // For off-schedule pickups
                ->orWhere('assigned_collector_id', $request->collector_id);
            });
        }

        // Clone queries to avoid reusing executed queries
        $total = (clone $query)->count();

        $byStatus = (clone $query)->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $byConfirmation = (clone $query)->select('confirmation_status', DB::raw('count(*) as count'))
            ->groupBy('confirmation_status')
            ->pluck('count', 'confirmation_status');

        $totalAmount = (clone $query)->sum('total_amount');
        // Count both 'collected' (scheduled) and 'completed' (request) as completed
        $totalCompleted = (clone $query)->whereIn('status', ['collected', 'completed'])->count();
        $completionRate = $total > 0 ? round(($totalCompleted / $total) * 100, 2) : 0;

        // Get active collectors today (simplified - just count collectors with role)
        $activeCollectors = User::whereHas('roles', function($q) {
            $q->where('name', 'collector');
        })->count();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date->format('Y-m-d'),
                'total_pickups' => $total,
                'by_status' => [
                    'pending' => $byStatus['pending'] ?? 0,
                    'confirmed' => $byStatus['confirmed'] ?? 0,
                    'in_progress' => $byStatus['in_progress'] ?? 0,
                    'collected' => $byStatus['collected'] ?? 0,
                    'skipped' => $byStatus['skipped'] ?? 0,
                    'completed' => $byStatus['completed'] ?? 0,
                    'cancelled' => $byStatus['cancelled'] ?? 0,
                ],
                'by_confirmation' => [
                    'pending' => $byConfirmation['pending'] ?? 0,
                    'confirmed' => $byConfirmation['confirmed'] ?? 0,
                    'skipped' => $byConfirmation['skipped'] ?? 0,
                    'no_waste' => $byConfirmation['no_waste'] ?? 0,
                ],
                'total_amount' => $totalAmount,
                'completion_rate' => $completionRate,
                'active_collectors' => $activeCollectors,
            ]
        ]);
    }

    /**
     * Get collector information for a pickup
     */
    private function getCollectorInfo($pickup)
    {
        // For off-schedule pickups, check assigned_collector_id first
        if ($pickup->assigned_collector_id) {
            $collector = User::find($pickup->assigned_collector_id);
            if ($collector) {
                return [
                    'id' => $collector->id,
                    'name' => $collector->name,
                    'email' => $collector->email ?? '-',
                    'phone' => $collector->phone ?? '-',
                ];
            }
        }

        // For scheduled pickups, get from schedule assignments
        if (!$pickup->schedule) {
            return [
                'id' => null,
                'name' => '-',
                'email' => '-',
                'phone' => '-',
            ];
        }

        $assignment = $pickup->schedule->collectorAssignments()
            ->with('user')
            ->where('start_date', '<=', $pickup->pickup_date)
            ->where(function($q) use ($pickup) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $pickup->pickup_date);
            })
            ->first();

        if (!$assignment || !$assignment->user) {
            return [
                'id' => null,
                'name' => '-',
                'email' => '-',
                'phone' => '-',
            ];
        }

        return [
            'id' => $assignment->user->id,
            'name' => $assignment->user->name,
            'email' => $assignment->user->email ?? '-',
            'phone' => $assignment->user->phone ?? '-',
        ];
    }

    /**
     * Show pickup detail page (View)
     */
    public function showView($id)
    {
        $pickup = WasteDelivery::with([
            'serviceAccount.user',
            'serviceAccount.area',
            'schedule.collectorAssignments.user',
            'wasteDeliveryItems.waste',
            'wasteDeliveryItems.pocketSize',
            'invoice'
        ])->findOrFail($id);

        return view('admin.pickups.show', compact('pickup'));
    }

    /**
     * Get pickup detail (API/JSON)
     *
     * GET /api/v1/admin/pickups/{id}
     */
    public function show($id)
    {
        $pickup = WasteDelivery::with([
            'serviceAccount.user',
            'serviceAccount.area',
            'schedule.collectorAssignments.user',
            'wasteDeliveryItems.waste',
            'wasteDeliveryItems.pocketSize',
            'invoice'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $pickup->id,
                'pickup_date' => $pickup->pickup_date->format('Y-m-d'),
                'scheduled_at' => $pickup->scheduled_at ? $pickup->scheduled_at->format('Y-m-d H:i') : null,
                'status' => $pickup->status,
                'confirmation_status' => $pickup->confirmation_status,
                'resident' => [
                    'name' => optional($pickup->serviceAccount)->name ?? '-',
                    'account_number' => optional($pickup->serviceAccount)->account_number ?? '-',
                    'address' => optional($pickup->serviceAccount)->address ?? '-',
                    'phone' => optional($pickup->serviceAccount)->contact_phone ?? '-',
                ],
                'area' => [
                    'name' => optional(optional($pickup->serviceAccount)->area)->name ?? '-',
                ],
                'collector' => $this->getCollectorInfo($pickup),
                'waste_items' => $pickup->wasteDeliveryItems->map(function($item) {
                    return [
                        'waste_name' => optional($item->waste)->category ?? '-',
                        'pocket_size' => optional($item->pocketSize)->name ?? '-',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                    ];
                }),
                'total_amount' => $pickup->total_amount,
                'photo_url' => $pickup->photo_url ? Storage::url($pickup->photo_url) : null,
                'confirmed_at' => $pickup->confirmed_at ? $pickup->confirmed_at->format('Y-m-d H:i:s') : null,
                'collected_at' => $pickup->collected_at ? $pickup->collected_at->format('Y-m-d H:i:s') : null,
                'completed_at' => $pickup->completed_at ? $pickup->completed_at->format('Y-m-d H:i:s') : null,
                'resident_note' => $pickup->resident_note,
                'collector_notes' => $pickup->collector_notes,
                'invoice' => $pickup->invoice ? [
                    'invoice_number' => $pickup->invoice->invoice_number,
                    'status' => $pickup->invoice->status,
                    'total_amount' => $pickup->invoice->total_amount,
                    'issued_at' => $pickup->invoice->issued_at->format('Y-m-d H:i'),
                    'paid_at' => $pickup->invoice->paid_at ? $pickup->invoice->paid_at->format('Y-m-d H:i') : null,
                ] : null,
            ]
        ]);
    }

    /**
     * Approve a pickup
     */
    public function approve($id)
    {
        try {
            $pickup = WasteDelivery::findOrFail($id);
            
            if ($pickup->confirmation_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengambilan ini sudah dikonfirmasi sebelumnya'
                ], 400);
            }
            
            $pickup->update([
                'confirmation_status' => 'confirmed',
                'confirmed_at' => now(),
                'confirmed_by' => auth()->id()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pengambilan sampah berhasil diapprove'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error approving pickup: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat approve pengambilan'
            ], 500);
        }
    }

    /**
     * Reject a pickup
     */
    public function reject(Request $request, $id)
    {
        try {
            $pickup = WasteDelivery::findOrFail($id);
            
            if ($pickup->confirmation_status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengambilan ini sudah dikonfirmasi sebelumnya'
                ], 400);
            }
            
            $reason = $request->input('reason', 'Tidak ada alasan');
            
            $pickup->update([
                'confirmation_status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => auth()->id(),
                'rejection_reason' => $reason
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Pengambilan sampah berhasil direject'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error rejecting pickup: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat reject pengambilan'
            ], 500);
        }
    }
}
