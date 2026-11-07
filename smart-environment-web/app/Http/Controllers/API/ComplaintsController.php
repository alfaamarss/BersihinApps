<?php

namespace App\Http\Controllers\API;

use App\Models\Complaint;

class ComplaintsController extends BaseResourceController
{
    protected string $modelClass = Complaint::class;
    protected array $rulesStore = [
        'user_id' => 'required|exists:users,id',
        'service_account_id' => 'nullable|exists:service_accounts,id',
        'assignee_id' => 'nullable|exists:users,id',
        'type' => 'required|string',
        'description' => 'required|string',
        'address' => 'nullable|string',
        'latitude' => 'nullable|numeric|between:-90,90',
        'longitude' => 'nullable|numeric|between:-180,180',
        'evidence_photo_url' => 'nullable|string',
        'status' => 'required|in:open,in_progress,resolved,rejected',
    ];
    protected array $rulesUpdate = [
        'user_id' => 'sometimes|exists:users,id',
        'service_account_id' => 'nullable|exists:service_accounts,id',
        'assignee_id' => 'nullable|exists:users,id',
        'type' => 'sometimes|string',
        'description' => 'sometimes|string',
        'address' => 'sometimes|string',
        'latitude' => 'nullable|numeric|between:-90,90',
        'longitude' => 'nullable|numeric|between:-180,180',
        'evidence_photo_url' => 'nullable|string',
        'status' => 'sometimes|in:open,in_progress,resolved,rejected',
    ];
    protected array $filterable = ['status','type'];
    protected array $sortable = ['id'];
    protected ?string $permissionPrefix = 'complaint';
}
