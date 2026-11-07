<?php

namespace App\Http\Controllers\API\Mobile\Resident;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintHistory;
use App\Notifications\Admin\NewComplaintNotification;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComplaintController extends Controller
{
    use ApiResponse;

    /**
     * Get list of complaints for current user
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Complaint::query()
            ->where('user_id', $user->id)
            ->with(['serviceAccount', 'assignee:id,name', 'photos']);

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Filter by service account
        if ($serviceAccountId = $request->input('service_account_id')) {
            $query->where('service_account_id', $serviceAccountId);
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
                'rejection_reason' => $complaint->rejection_reason,
                'photos' => $complaint->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => Storage::url($photo->photo_url),
                        'order' => $photo->order,
                    ];
                }),
                'service_account' => $complaint->serviceAccount ? [
                    'id' => $complaint->serviceAccount->id,
                    'name' => $complaint->serviceAccount->name,
                    'address' => $complaint->serviceAccount->address,
                ] : null,
                'assignee' => $complaint->assignee ? [
                    'id' => $complaint->assignee->id,
                    'name' => $complaint->assignee->name,
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
     * Create a new complaint
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'service_account_id' => 'nullable|exists:service_accounts,id',
            'type' => 'required|string|max:100',
            'description' => 'required|string|max:1000',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'evidence_photos' => 'nullable|array|max:5', // Max 5 photos
            'evidence_photos.*' => 'image|max:5120', // Each photo max 5MB
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        // If service_account_id is provided, verify it belongs to the user
        if ($request->service_account_id) {
            $serviceAccount = $user->serviceAccounts()->find($request->service_account_id);
            if (!$serviceAccount) {
                return $this->error('Service account not found or does not belong to you.', 404);
            }
        }

        $data = [
            'user_id' => $user->id,
            'service_account_id' => $request->service_account_id,
            'type' => $request->type,
            'description' => $request->description,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'open',
        ];

        $complaint = Complaint::create($data);

        // Catat history awal - gunakan model langsung untuk memastikan tersimpan
        try {
            ComplaintHistory::create([
                'complaint_id' => $complaint->id,
                'status' => 'open',
                'description' => 'Pengaduan dibuat oleh warga',
                'note' => null,
                'changed_by' => $user->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create complaint history: ' . $e->getMessage());
        }

        // Handle multiple photo uploads
        if ($request->hasFile('evidence_photos')) {
            foreach ($request->file('evidence_photos') as $index => $file) {
                $path = $file->store('complaints', 'public');
                $complaint->photos()->create([
                    'photo_url' => $path,
                    'order' => $index,
                ]);
            }
        }

        // Load photos relationship
        $complaint->load('photos');

        // Send notification to admins about new complaint
        try {
            $notificationService = app(NotificationService::class);
            $notificationService->sendToAdmins(new NewComplaintNotification($complaint));
        } catch (\Exception $e) {
            Log::error('Failed to send new complaint notification: ' . $e->getMessage());
        }

        return $this->created([
            'id' => $complaint->id,
            'type' => $complaint->type,
            'description' => $complaint->description,
            'address' => $complaint->address,
            'latitude' => $complaint->latitude,
            'longitude' => $complaint->longitude,
            'status' => $complaint->status,
            'resolution_photo' => null,
            'rejection_reason' => null,
            'photos' => $complaint->photos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'url' => Storage::url($photo->photo_url),
                    'order' => $photo->order,
                ];
            }),
            'service_account' => $complaint->serviceAccount ? [
                'id' => $complaint->serviceAccount->id,
                'name' => $complaint->serviceAccount->name,
                'address' => $complaint->serviceAccount->address,
            ] : null,
            'created_at' => $complaint->created_at?->toIso8601String(),
        ], 'Complaint submitted successfully.');
    }

    /**
     * Get single complaint detail
     */
    public function show(Request $request, string $id)
    {
        $user = $request->user();

        $complaint = Complaint::with(['serviceAccount.area', 'assignee', 'photos'])
            ->where('user_id', $user->id)
            ->find($id);

        if (!$complaint) {
            return $this->error('Complaint not found.', 404);
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
                'rejection_reason' => $complaint->rejection_reason,
                'photos' => $complaint->photos->map(function ($photo) {
                    return [
                        'id' => $photo->id,
                        'url' => Storage::url($photo->photo_url),
                        'order' => $photo->order,
                    ];
                }),
                'service_account' => $complaint->serviceAccount ? [
                    'id' => $complaint->serviceAccount->id,
                    'name' => $complaint->serviceAccount->name,
                    'address' => $complaint->serviceAccount->address,
                    'contact_phone' => $complaint->serviceAccount->contact_phone,
                    'area' => $complaint->serviceAccount->area ? [
                        'id' => $complaint->serviceAccount->area->id,
                        'name' => $complaint->serviceAccount->area->name,
                    ] : null,
                ] : null,
                'assignee' => $complaint->assignee ? [
                    'id' => $complaint->assignee->id,
                    'name' => $complaint->assignee->name,
                ] : null,
                'created_at' => $complaint->created_at?->toIso8601String(),
                'updated_at' => $complaint->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Update complaint (only for open complaints)
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();

        $complaint = Complaint::where('user_id', $user->id)->find($id);

        if (!$complaint) {
            return $this->error('Complaint not found.', 404);
        }

        // Only allow updates if status is 'open'
        if ($complaint->status !== 'open') {
            return $this->error('Cannot update complaint with status: ' . $complaint->status, 400);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|string|max:100',
            'description' => 'sometimes|string|max:1000',
            'address' => 'sometimes|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'evidence_photos' => 'nullable|array|max:5', // Max 5 photos
            'evidence_photos.*' => 'image|max:5120',
            'delete_photo_ids' => 'nullable|array',
            'delete_photo_ids.*' => 'integer|exists:complaint_photos,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $data = [];

        if ($request->has('type')) {
            $data['type'] = $request->type;
        }

        if ($request->has('description')) {
            $data['description'] = $request->description;
        }

        if ($request->has('address')) {
            $data['address'] = $request->address;
        }

        if ($request->has('latitude')) {
            $data['latitude'] = $request->latitude;
        }

        if ($request->has('longitude')) {
            $data['longitude'] = $request->longitude;
        }

        $complaint->update($data);

        // Handle photo deletion
        if ($request->has('delete_photo_ids') && is_array($request->delete_photo_ids)) {
            $complaint->photos()->whereIn('id', $request->delete_photo_ids)->delete();
        }

        // Handle new photo uploads
        if ($request->hasFile('evidence_photos')) {
            $maxOrder = $complaint->photos()->max('order') ?? -1;
            foreach ($request->file('evidence_photos') as $index => $file) {
                $path = $file->store('complaints', 'public');
                $complaint->photos()->create([
                    'photo_url' => $path,
                    'order' => $maxOrder + $index + 1,
                ]);
            }
        }

        // Reload complaint with photos
        $complaint->load('photos');

        return $this->ok([
            'id' => $complaint->id,
            'type' => $complaint->type,
            'description' => $complaint->description,
            'address' => $complaint->address,
            'latitude' => $complaint->latitude,
            'longitude' => $complaint->longitude,
            'status' => $complaint->status,
            'resolution_photo' => $complaint->resolution_photo ? Storage::url($complaint->resolution_photo) : null,
            'rejection_reason' => $complaint->rejection_reason,
            'photos' => $complaint->photos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'url' => Storage::url($photo->photo_url),
                    'order' => $photo->order,
                ];
            }),
            'updated_at' => $complaint->updated_at?->toIso8601String(),
        ], 'Complaint updated successfully.');
    }

    /**
     * Cancel complaint (change status to rejected by user)
     */
    public function cancel(Request $request, string $id)
    {
        $user = $request->user();

        $complaint = Complaint::where('user_id', $user->id)->find($id);

        if (!$complaint) {
            return $this->error('Complaint not found.', 404);
        }

        // Only allow cancellation if status is 'open' or 'in_progress'
        if (!in_array($complaint->status, ['open', 'in_progress'])) {
            return $this->error('Cannot cancel complaint with status: ' . $complaint->status, 400);
        }

        $complaint->update(['status' => 'rejected']);

        return $this->ok([
            'id' => $complaint->id,
            'status' => $complaint->status,
        ], 'Complaint cancelled successfully.');
    }

    /**
     * Confirm resolution of complaint by resident
     * This is used when collector has completed the task and awaiting resident confirmation
     * Only applicable for complaints with type 'sampah_tidak_diangkut'
     */
    public function confirmResolution(Request $request, string $id)
    {
        $user = $request->user();

        $complaint = Complaint::where('user_id', $user->id)->find($id);

        if (!$complaint) {
            return $this->error('Complaint not found.', 404);
        }

        // Only allow confirmation if status is 'pending_confirmation'
        if ($complaint->status !== 'pending_confirmation') {
            return $this->error('Pengaduan ini tidak dalam status menunggu konfirmasi.', 400);
        }

        // Validate the confirmation action
        $validator = Validator::make($request->all(), [
            'confirmed' => 'required|boolean',
            'rejection_note' => 'nullable|string|max:500', // Optional note if resident rejects
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $confirmed = $request->boolean('confirmed');

        if ($confirmed) {
            // Resident confirms the resolution
            $complaint->update(['status' => 'resolved']);
            $complaint->addHistory(
                'resolved', 
                'Penyelesaian dikonfirmasi oleh warga ' . $user->name, 
                null, 
                $user->id
            );

            return $this->ok([
                'id' => $complaint->id,
                'status' => $complaint->status,
                'updated_at' => $complaint->updated_at?->toIso8601String(),
            ], 'Terima kasih! Pengaduan telah dikonfirmasi selesai.');
        } else {
            // Resident rejects the resolution - return to in_progress
            $rejectionNote = $request->input('rejection_note', 'Warga tidak menerima penyelesaian');
            $complaint->update(['status' => 'in_progress']);
            $complaint->addHistory(
                'in_progress', 
                'Warga ' . $user->name . ' tidak menerima penyelesaian: ' . $rejectionNote, 
                $rejectionNote, 
                $user->id
            );

            return $this->ok([
                'id' => $complaint->id,
                'status' => $complaint->status,
                'updated_at' => $complaint->updated_at?->toIso8601String(),
            ], 'Pengaduan dikembalikan ke status dalam proses.');
        }
    }

    /**
     * Get complaint statistics
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        $total = Complaint::where('user_id', $user->id)->count();
        $open = Complaint::where('user_id', $user->id)->where('status', 'open')->count();
        $inProgress = Complaint::where('user_id', $user->id)->where('status', 'in_progress')->count();
        $resolved = Complaint::where('user_id', $user->id)->where('status', 'resolved')->count();
        $rejected = Complaint::where('user_id', $user->id)->where('status', 'rejected')->count();

        return $this->ok([
            'statistics' => [
                'total' => $total,
                'open' => $open,
                'in_progress' => $inProgress,
                'resolved' => $resolved,
                'rejected' => $rejected,
            ],
        ]);
    }
}
