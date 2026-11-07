<?php

namespace App\Http\Controllers\API\Mobile\Collector;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Notifications\Admin\CollectorCompletedComplaintNotification;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    use ApiResponse;

    /**
     * Get list of complaints assigned to current collector
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Complaint::query()
            ->where('assignee_id', $user->id)
            ->with(['user', 'serviceAccount', 'photos']);

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $perPage = $request->input('per_page', 15);
        $complaints = $query->orderByDesc('created_at')->paginate($perPage);

        $data = $complaints->getCollection()->map(function ($complaint) {
            return [
                'id' => $complaint->id,
                'type' => $complaint->type,
                'description' => $complaint->description,
                'address' => $complaint->address,
                'latitude' => $complaint->latitude,
                'longitude' => $complaint->longitude,
                'status' => $complaint->status,
                'resolution_photo' => $complaint->resolution_photo ? Storage::url($complaint->resolution_photo) : null,
                'photos' => $complaint->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => Storage::url($photo->photo_url),
                        'order' => $photo->order,
                    ];
                }),
                'reporter' => [
                    'id' => $complaint->user->id,
                    'name' => $complaint->user->name,
                    'email' => $complaint->user->email,
                ],
                'service_account' => $complaint->serviceAccount ? [
                    'id' => $complaint->serviceAccount->id,
                    'name' => $complaint->serviceAccount->name,
                    'address' => $complaint->serviceAccount->address,
                    'latitude' => $complaint->serviceAccount->latitude,
                    'longitude' => $complaint->serviceAccount->longitude,
                    'contact_phone' => $complaint->serviceAccount->contact_phone,
                    'account_number' => $complaint->serviceAccount->account_number,
                ] : null,
                'created_at' => $complaint->created_at?->toIso8601String(),
                'updated_at' => $complaint->updated_at?->toIso8601String(),
            ];
        });

        return $this->ok([
            'complaints' => $data,
            'meta' => [
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'per_page' => $complaints->perPage(),
                'total' => $complaints->total(),
            ],
        ]);
    }

    /**
     * Get single complaint detail
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $complaint = Complaint::with(['user', 'serviceAccount.area', 'photos'])
            ->where('assignee_id', $user->id)
            ->find($id);

        if (!$complaint) {
            return $this->error('Complaint not found or not assigned to you.', 404);
        }

        return $this->ok([
            'complaint' => [
                'id' => $complaint->id,
                'type' => $complaint->type,
                'description' => $complaint->description,
                'address' => $complaint->address,
                'latitude' => $complaint->latitude,
                'longitude' => $complaint->longitude,
                'status' => $complaint->status,
                'resolution_photo' => $complaint->resolution_photo ? Storage::url($complaint->resolution_photo) : null,
                'photos' => $complaint->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => Storage::url($photo->photo_url),
                        'order' => $photo->order,
                    ];
                }),
                'reporter' => [
                    'id' => $complaint->user->id,
                    'name' => $complaint->user->name,
                    'email' => $complaint->user->email,
                ],
                'service_account' => $complaint->serviceAccount ? [
                    'id' => $complaint->serviceAccount->id,
                    'name' => $complaint->serviceAccount->name,
                    'address' => $complaint->serviceAccount->address,
                    'latitude' => $complaint->serviceAccount->latitude,
                    'longitude' => $complaint->serviceAccount->longitude,
                    'contact_phone' => $complaint->serviceAccount->contact_phone,
                    'account_number' => $complaint->serviceAccount->account_number,
                    'area' => $complaint->serviceAccount->area ? [
                        'id' => $complaint->serviceAccount->area->id,
                        'name' => $complaint->serviceAccount->area->name,
                    ] : null,
                ] : null,
                'created_at' => $complaint->created_at?->toIso8601String(),
                'updated_at' => $complaint->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update complaint status by collector
     */
    public function updateStatus(Request $request, string $id)
    {
        $user = $request->user();

        $complaint = Complaint::where('assignee_id', $user->id)->find($id);

        if (!$complaint) {
            return $this->error('Complaint not found or not assigned to you.', 404);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['in_progress', 'resolved'])],
            'resolution_photo' => ['nullable', 'image', 'max:5120'], // 5MB max
        ]);

        $oldStatus = $complaint->status;
        
        // Untuk kategori sampah_tidak_diangkut, jika collector menyelesaikan,
        // status menjadi pending_confirmation (menunggu konfirmasi warga)
        $finalStatus = $validated['status'];
        if ($validated['status'] === 'resolved' && $complaint->type === 'sampah_tidak_diangkut') {
            $finalStatus = 'pending_confirmation';
        }
        
        $data = ['status' => $finalStatus];

        // If status is resolved/pending_confirmation and photo is uploaded, save the photo
        if (in_array($validated['status'], ['resolved']) && $request->hasFile('resolution_photo')) {
            $path = $request->file('resolution_photo')->store('complaints/resolutions', 'public');
            $data['resolution_photo'] = $path;
        }

        $complaint->update($data);

        // Catat history jika status berubah
        if ($oldStatus !== $finalStatus) {
            if ($finalStatus === 'pending_confirmation') {
                $description = 'Petugas ' . $user->name . ' telah menyelesaikan tugas, menunggu konfirmasi warga';
            } elseif ($finalStatus === 'resolved') {
                $description = 'Pengaduan diselesaikan oleh petugas ' . $user->name;
            } else {
                $description = 'Status diubah oleh petugas ' . $user->name;
            }
            
            $complaint->addHistory($finalStatus, $description, null, $user->id);

            // Send notification to admins when collector completes complaint
            // Only for categories: sampah_tidak_diangkut, sampah_menumpuk
            if (in_array($finalStatus, ['resolved', 'pending_confirmation']) 
                && in_array($complaint->type, ['sampah_tidak_diangkut', 'sampah_menumpuk'])) {
                try {
                    $notificationService = app(NotificationService::class);
                    $notificationService->sendToAdmins(new CollectorCompletedComplaintNotification($complaint));
                } catch (\Exception $e) {
                    Log::error('Failed to send collector completed complaint notification: ' . $e->getMessage());
                }
            }
        }

        return $this->ok([
            'id' => $complaint->id,
            'status' => $complaint->status,
            'resolution_photo' => $complaint->resolution_photo ? Storage::url($complaint->resolution_photo) : null,
            'updated_at' => $complaint->updated_at?->toIso8601String(),
        ], $finalStatus === 'pending_confirmation' 
            ? 'Bukti penyelesaian terkirim. Menunggu konfirmasi warga.' 
            : 'Status updated successfully.');
    }

    /**
     * Get complaint statistics for collector
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        $total = Complaint::where('assignee_id', $user->id)->count();
        $inProgress = Complaint::where('assignee_id', $user->id)->where('status', 'in_progress')->count();
        $resolved = Complaint::where('assignee_id', $user->id)->where('status', 'resolved')->count();

        return $this->ok([
            'statistics' => [
                'total' => $total,
                'in_progress' => $inProgress,
                'resolved' => $resolved,
            ],
        ]);
    }
}
