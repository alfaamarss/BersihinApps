<?php

namespace App\Http\Controllers\API\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\ServiceAccountResource;
use App\Models\CollectorAssignment;
use App\Models\Rw;
use App\Models\ServiceAccount;
use App\Models\User;
use App\Notifications\User\NewServiceAccountCreatedNotification;
use App\Notifications\Admin\NewRwNeedsCollectorNotification;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

class ServiceAccountsController extends Controller
{
    use ApiResponse;

    public function __construct(protected NotificationService $notificationService)
    {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $query = $user->serviceAccounts()
            ->with([
                'area.parent',
                'rw',
                'schedules',
                'invoices' => fn ($q) => $q->where('status', 'not_paid'),
                'wasteDeliveries' => fn ($q) => $q->latest('scheduled_at')->limit(1),
            ]);

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Filter by area_id (kelurahan)
        if ($areaId = $request->query('area_id')) {
            $query->where('area_id', $areaId);
        }

        // Filter by rw_id
        if ($rwId = $request->query('rw_id')) {
            $query->where('rw_id', $rwId);
        }

        $perPage = min($request->integer('page.size', 10), 50);

        $accounts = $query->orderByDesc('created_at')->paginate($perPage);

        $items = ServiceAccountResource::collection($accounts->getCollection()->values())->toArray($request);

        return $this->ok(
            ['items' => $items],
            [
                'current_page' => $accounts->currentPage(),
                'per_page' => $accounts->perPage(),
                'total' => $accounts->total(),
                'last_page' => $accounts->lastPage(),
            ]
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'area_id' => ['required', 'exists:areas,id'],
            'rw_name' => ['nullable', 'string', 'max:50', 'regex:/^RW\s+\d{1,3}$/i'], // Format: "RW 01", "RW 1", etc
            'address' => ['required', 'string'],
            'latitude' => ['required', 'numeric', 'gte:-90', 'lte:90'],
            'longitude' => ['required', 'numeric', 'gte:-180', 'lte:180'],
            'note' => ['nullable', 'string'],
        ]);

        // Auto-create RW if provided and doesn't exist
        $rwId = null;
        if (!empty($data['rw_name'])) {
            $rwName = strtoupper(trim($data['rw_name'])); // Normalize: "rw 1" -> "RW 1"
            
            // Ensure proper format: "RW XX"
            if (preg_match('/^RW\s+(\d+)$/i', $rwName, $matches)) {
                // Already has "RW" prefix: "RW 5" -> "RW 05"
                $rwNumber = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $rwName = "RW {$rwNumber}";
            } elseif (preg_match('/^\d+$/', $rwName)) {
                // Only number: "5" -> "RW 05"
                $rwNumber = str_pad($rwName, 2, '0', STR_PAD_LEFT);
                $rwName = "RW {$rwNumber}";
            }
            // Otherwise keep as-is (e.g., custom RW names)
            
            $rw = Rw::firstOrCreate(
                [
                    'kelurahan_id' => $data['area_id'],
                    'name' => $rwName,
                ],
                [
                    'kelurahan_id' => $data['area_id'],
                    'name' => $rwName,
                ]
            );
            
            $rwId = $rw->id;
            $isNewRw = $rw->wasRecentlyCreated;
        } else {
            $isNewRw = false;
        }

        unset($data['rw_name']); // Remove rw_name from data
        $data['rw_id'] = $rwId; // Add rw_id instead

        $serviceAccount = $user->serviceAccounts()->create(array_merge($data, [
            'status' => 'active',
        ]));

        $serviceAccount->load([
            'area.parent',
            'rw',
            'schedules',
            'invoices' => fn ($q) => $q->where('status', 'not_paid'),
            'wasteDeliveries' => fn ($q) => $q->latest('scheduled_at')->limit(1),
        ]);

        // Send notification to user
        $user->notify(new NewServiceAccountCreatedNotification($serviceAccount));

        // If this is a new RW or RW has no collector assigned, notify admins
        if ($isNewRw) {
            $this->notificationService->sendToAdmins(
                new NewRwNeedsCollectorNotification($serviceAccount->rw)
            );
        } elseif ($serviceAccount->rw) {
            // Check if RW has any active collector assignments
            $hasCollector = CollectorAssignment::where('rw_id', $serviceAccount->rw_id)
                ->where(function ($q) {
                    $q->whereNull('end_date')
                      ->orWhere('end_date', '>=', now()->toDateString());
                })
                ->exists();
            
            if (!$hasCollector) {
                $this->notificationService->sendToAdmins(
                    new NewRwNeedsCollectorNotification($serviceAccount->rw)
                );
            }
        }

        return $this->ok(ServiceAccountResource::make($serviceAccount)->toArray($request));
    }

    public function show(Request $request, string $id)
    {
        $serviceAccount = $this->findForUser($request, $id);

        return $this->ok(ServiceAccountResource::make($serviceAccount)->toArray($request));
    }

    public function update(Request $request, string $id)
    {
        $serviceAccount = $this->findForUser($request, $id, loadRelations: false);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'contact_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'area_id' => ['sometimes', 'exists:areas,id'],
            'rw_name' => ['nullable', 'string', 'max:50', 'regex:/^RW\s+\d{1,3}$/i'],
            'address' => ['sometimes', 'string'],
            'latitude' => ['sometimes', 'numeric', 'gte:-90', 'lte:90'],
            'longitude' => ['sometimes', 'numeric', 'gte:-180', 'lte:180'],
            'note' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        // Auto-create RW if provided and doesn't exist
        if (isset($data['rw_name']) && !empty($data['rw_name'])) {
            $rwName = strtoupper(trim($data['rw_name']));
            
            // Ensure proper format: "RW XX"
            if (preg_match('/^RW\s+(\d+)$/i', $rwName, $matches)) {
                $rwNumber = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $rwName = "RW {$rwNumber}";
            }
            
            $areaId = $data['area_id'] ?? $serviceAccount->area_id;
            
            $rw = Rw::firstOrCreate(
                [
                    'kelurahan_id' => $areaId,
                    'name' => $rwName,
                ],
                [
                    'kelurahan_id' => $areaId,
                    'name' => $rwName,
                ]
            );
            
            $data['rw_id'] = $rw->id;
        }

        unset($data['rw_name']);

        $serviceAccount->fill($data);
        $serviceAccount->save();

        $serviceAccount->load([
            'area.parent',
            'schedules',
            'invoices' => fn ($q) => $q->where('status', 'not_paid'),
            'wasteDeliveries' => fn ($q) => $q->latest('scheduled_at')->limit(1),
        ]);

        return $this->ok(ServiceAccountResource::make($serviceAccount)->toArray($request));
    }

    public function destroy(Request $request, string $id)
    {
        $serviceAccount = $this->findForUser($request, $id, loadRelations: false);
        $serviceAccount->delete();

        return $this->ok(['deleted' => true]);
    }

    protected function findForUser(Request $request, string $id, bool $loadRelations = true): ServiceAccount
    {
        $relations = $loadRelations ? [
            'area.parent',
            'schedules',
            'invoices' => fn ($q) => $q->where('status', 'not_paid'),
            'wasteDeliveries' => fn ($q) => $q->latest('scheduled_at')->limit(1),
        ] : [];

        $query = $request->user()->serviceAccounts();

        if (! empty($relations)) {
            $query->with($relations);
        }

        try {
            return $query->where('service_accounts.id', $id)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            throw new HttpResponseException($this->fail('Service account not found', 404));
        }
    }
}
