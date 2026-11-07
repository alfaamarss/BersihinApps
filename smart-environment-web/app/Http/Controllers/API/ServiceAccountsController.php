<?php

namespace App\Http\Controllers\API;

use App\Models\ServiceAccount;

class ServiceAccountsController extends BaseResourceController
{
    protected string $modelClass = ServiceAccount::class;
    protected array $rulesStore = [
        'user_id' => 'required|exists:users,id',
        'area_id' => 'required|exists:areas,id',
        'rw_id' => 'nullable|exists:rws,id',
        'name' => 'required|string',
        'contact_phone' => 'nullable|string',
        'address' => 'required|string',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'note' => 'nullable|string',
        'status' => 'required|in:active,inactive',
    ];
    protected array $rulesUpdate = [
        'user_id' => 'sometimes|exists:users,id',
        'area_id' => 'sometimes|exists:areas,id',
        'rw_id' => 'nullable|exists:rws,id',
        'name' => 'sometimes|string',
        'contact_phone' => 'nullable|string',
        'address' => 'sometimes|string',
        'latitude' => 'sometimes|numeric',
        'longitude' => 'sometimes|numeric',
        'note' => 'nullable|string',
        'status' => 'sometimes|in:active,inactive',
    ];
    protected array $filterable = ['name','status','area_id','rw_id'];
    protected array $sortable = ['id','name'];
    protected array $relations = ['area', 'rw', 'user'];
    protected ?string $permissionPrefix = 'service_account';
}
