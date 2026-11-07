<?php

namespace App\Http\Controllers\API;

use App\Models\WasteDelivery;

class WasteDeliveriesController extends BaseResourceController
{
    protected string $modelClass = WasteDelivery::class;
    protected array $rulesStore = [
        'service_account_id' => 'required|exists:service_accounts,id',
        'user_id' => 'required|exists:users,id',
        'status' => 'required|in:scheduled,on_progress,collected,cancelled,completed',
        'scheduled_at' => 'required|date',
        'note' => 'nullable|string',
        'total_amount' => 'required|numeric',
    ];
    protected array $rulesUpdate = [
        'service_account_id' => 'sometimes|exists:service_accounts,id',
        'user_id' => 'sometimes|exists:users,id',
        'status' => 'sometimes|in:scheduled,on_progress,collected,cancelled,completed',
        'scheduled_at' => 'sometimes|date',
        'note' => 'nullable|string',
        'total_amount' => 'sometimes|numeric',
    ];
    protected array $filterable = ['status'];
    protected array $sortable = ['id','scheduled_at'];
    protected ?string $permissionPrefix = 'waste_delivery';
}
