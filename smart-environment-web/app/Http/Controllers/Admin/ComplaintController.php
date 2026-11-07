<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintPhoto;
use App\Models\User;
use App\Models\ServiceAccount;
use App\Notifications\User\ComplaintStatusNotification;
use App\Notifications\Collector\NewComplaintTaskNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $query = Complaint::with(['user', 'serviceAccount', 'assignee', 'photos'])
            ->latest();

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by type
        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $complaints = $query->paginate(15)->withQueryString();

        // Get filter options
        $statusOptions = $this->getStatusOptions();
        $typeOptions = $this->getTypeOptions();
        
        // Get collectors for assignment dropdown
        $collectors = User::role('collector')->orderBy('name')->get();

        return view('admin.complaints.index', compact('complaints', 'statusOptions', 'typeOptions', 'collectors'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        $serviceAccounts = ServiceAccount::with('area')->orderBy('account_number')->get();
        // Only get users with collector role
        $assignees = User::role('collector')->orderBy('name')->get();
        
        $statusOptions = $this->getStatusOptions();
        $typeOptions = $this->getTypeOptions();

        return view('admin.complaints.create', compact('users', 'serviceAccounts', 'assignees', 'statusOptions', 'typeOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'service_account_id' => ['nullable', 'exists:service_accounts,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'type' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string'],
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'rejected'])],
            'photos.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $complaint = Complaint::create($data);

        // Catat history awal
        $complaint->addHistory($data['status'], 'Pengaduan dibuat oleh admin');

        // Handle photo uploads
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $photo) {
                $path = $photo->store('complaints', 'public');
                
                ComplaintPhoto::create([
                    'complaint_id' => $complaint->id,
                    'photo_url' => $path,
                    'order' => $index + 1,
                ]);
            }
        }

        return redirect()->route('admin.complaints.index')
            ->with('success', 'Pengaduan berhasil ditambahkan.');
    }

    public function show(Complaint $complaint)
    {
        $complaint->load(['user', 'serviceAccount', 'assignee', 'photos', 'histories']);
        
        // Get collectors for assignment
        $collectors = User::role('collector')->orderBy('name')->get();
        
        return view('admin.complaints.show', compact('complaint', 'collectors'));
    }

    public function edit(Complaint $complaint)
    {
        $complaint->load('photos');
        
        $users = User::orderBy('name')->get();
        $serviceAccounts = ServiceAccount::with('area')->orderBy('account_number')->get();
        // Only get users with collector role
        $assignees = User::role('collector')->orderBy('name')->get();
        
        $statusOptions = $this->getStatusOptions();
        $typeOptions = $this->getTypeOptions();

        return view('admin.complaints.edit', compact('complaint', 'users', 'serviceAccounts', 'assignees', 'statusOptions', 'typeOptions'));
    }

    public function update(Request $request, Complaint $complaint)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'service_account_id' => ['nullable', 'exists:service_accounts,id'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'type' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string'],
            'status' => ['required', Rule::in(['open', 'in_progress', 'resolved', 'rejected'])],
            'photos.*' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'remove_photos' => ['nullable', 'array'],
            'remove_photos.*' => ['exists:complaint_photos,id'],
        ]);

        // Remove selected photos
        if ($request->has('remove_photos')) {
            $photosToRemove = ComplaintPhoto::whereIn('id', $request->remove_photos)
                ->where('complaint_id', $complaint->id)
                ->get();
            
            foreach ($photosToRemove as $photo) {
                if (Storage::disk('public')->exists($photo->photo_url)) {
                    Storage::disk('public')->delete($photo->photo_url);
                }
                $photo->delete();
            }
        }

        // Handle new photo uploads
        if ($request->hasFile('photos')) {
            $lastOrder = $complaint->photos()->max('order') ?? 0;
            
            foreach ($request->file('photos') as $index => $photo) {
                $path = $photo->store('complaints', 'public');
                
                ComplaintPhoto::create([
                    'complaint_id' => $complaint->id,
                    'photo_url' => $path,
                    'order' => $lastOrder + $index + 1,
                ]);
            }
        }

        $complaint->update($data);

        return redirect()->route('admin.complaints.index')
            ->with('success', 'Pengaduan berhasil diperbarui.');
    }

    public function destroy(Complaint $complaint)
    {
        // Photos will be deleted automatically via model boot method
        $complaint->delete();

        return redirect()->route('admin.complaints.index')
            ->with('success', 'Pengaduan berhasil dihapus.');
    }

    /**
     * Assign collector to complaint
     */
    public function assignCollector(Request $request, Complaint $complaint)
    {
        $request->validate([
            'assignee_id' => 'required|exists:users,id',
        ]);

        // Verify the assignee is a collector
        $collector = User::role('collector')->find($request->assignee_id);
        
        if (!$collector) {
            return back()->with('error', 'User yang dipilih bukan collector.');
        }

        $oldStatus = $complaint->status;

        $complaint->update([
            'assignee_id' => $request->assignee_id,
            'status' => 'in_progress', // Auto change status to in_progress when assigned
        ]);

        // Catat history jika status berubah
        if ($oldStatus !== 'in_progress') {
            $complaint->addHistory(
                'in_progress',
                'Pengaduan ditugaskan ke ' . $collector->name
            );
        }

        // Send notification to user that complaint is being processed
        try {
            if ($complaint->user) {
                $complaint->user->notify(
                    new ComplaintStatusNotification($complaint, 'in_progress', $collector->name)
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to send complaint in_progress notification to user: ' . $e->getMessage());
        }

        // Send notification to collector about new task
        try {
            $collector->notify(new NewComplaintTaskNotification($complaint));
        } catch (\Exception $e) {
            Log::error('Failed to send new complaint task notification to collector: ' . $e->getMessage());
        }

        return back()->with('success', 'Kolektor berhasil ditugaskan.');
    }

    /**
     * Update complaint status
     */
    public function updateStatus(Request $request, Complaint $complaint)
    {
        // Kategori yang tidak memerlukan resolution_description (karena di-handle oleh collector/warga)
        $noResolutionDescriptionTypes = ['sampah_tidak_diangkut', 'sampah_menumpuk'];
        
        // Validasi: resolution_description wajib jika status resolved dan bukan kategori sampah
        $requiresResolutionDescription = $request->status === 'resolved' && !in_array($complaint->type, $noResolutionDescriptionTypes);
        
        $rules = [
            'status' => ['required', Rule::in(['open', 'in_progress', 'pending_confirmation', 'resolved', 'rejected'])],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string'],
            'resolution_description' => $requiresResolutionDescription ? ['required', 'string'] : ['nullable', 'string'],
        ];
        
        $validated = $request->validate($rules);

        // Validasi: Tidak bisa menolak jika sudah in_progress (sudah assign collector)
        if ($validated['status'] === 'rejected' && in_array($complaint->status, ['in_progress', 'pending_confirmation'])) {
            return back()->with('error', 'Tidak dapat menolak pengaduan yang sudah ditugaskan ke collector.');
        }

        // Validasi: Jika status sudah in_progress, hanya bisa ke resolved atau pending_confirmation
        if ($complaint->status === 'in_progress' && !in_array($validated['status'], ['in_progress', 'pending_confirmation', 'resolved'])) {
            return back()->with('error', 'Pengaduan yang sudah dalam proses hanya bisa diselesaikan.');
        }

        // Validasi: Jika status pending_confirmation, hanya bisa ke resolved (dari konfirmasi warga) atau kembali ke in_progress
        if ($complaint->status === 'pending_confirmation' && !in_array($validated['status'], ['pending_confirmation', 'in_progress', 'resolved'])) {
            return back()->with('error', 'Pengaduan yang menunggu konfirmasi hanya dapat diselesaikan oleh warga.');
        }

        $oldStatus = $complaint->status;
        $data = ['status' => $validated['status']];

        // If status is rejected, require rejection reason
        if ($validated['status'] === 'rejected') {
            $data['rejection_reason'] = $validated['rejection_reason'];
        } else {
            // Clear rejection reason if status changed from rejected
            $data['rejection_reason'] = null;
        }

        // If status is resolved, save resolution description (for non-collector categories)
        if ($validated['status'] === 'resolved' && !empty($validated['resolution_description'])) {
            $data['resolution_description'] = $validated['resolution_description'];
        }

        $complaint->update($data);

        // Catat history jika status berubah
        if ($oldStatus !== $validated['status']) {
            $note = $validated['status'] === 'rejected' ? $validated['rejection_reason'] : null;
            // Jika resolved dengan resolution_description, tambahkan ke note
            if ($validated['status'] === 'resolved' && !empty($validated['resolution_description'])) {
                $note = $validated['resolution_description'];
            }
            $complaint->addHistory($validated['status'], null, $note);

            // Send notification to user about status change
            try {
                if ($complaint->user && in_array($validated['status'], ['in_progress', 'resolved', 'rejected', 'pending_confirmation'])) {
                    $collectorName = $complaint->assignee?->name;
                    $complaint->user->notify(
                        new ComplaintStatusNotification($complaint, $validated['status'], $collectorName)
                    );
                }
            } catch (\Exception $e) {
                Log::error('Failed to send complaint status notification: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Status pengaduan berhasil diperbarui.');
    }

    private function getStatusOptions()
    {
        return [
            ['value' => 'open', 'label' => 'Menunggu'],
            ['value' => 'in_progress', 'label' => 'Dalam Proses'],
            ['value' => 'pending_confirmation', 'label' => 'Menunggu Konfirmasi'],
            ['value' => 'resolved', 'label' => 'Selesai'],
            ['value' => 'rejected', 'label' => 'Ditolak'],
        ];
    }

    private function getTypeOptions()
    {
        return [
            ['value' => 'jadwal', 'label' => 'Jadwal Pengambilan'],
            ['value' => 'layanan', 'label' => 'Kualitas Layanan'],
            ['value' => 'petugas', 'label' => 'Petugas'],
            ['value' => 'sampah', 'label' => 'Pengelolaan Sampah'],
            ['value' => 'tagihan', 'label' => 'Tagihan'],
            ['value' => 'lainnya', 'label' => 'Lainnya'],
        ];
    }
}
