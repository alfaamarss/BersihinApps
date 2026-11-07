<?php

namespace App\Http\Controllers\API;

use App\Models\WastePricing;

class WastePricingsController extends BaseResourceController
{
    protected string $modelClass = WastePricing::class;
    protected array $rulesStore = [
        'waste_id' => 'required|exists:wastes,id',
        'pocket_size_id' => 'required|exists:pocket_sizes,id',
        'price' => 'required|numeric',
        'effective_from' => 'required|date',
        'effective_to' => 'nullable|date',
    ];
    protected array $rulesUpdate = [
        'waste_id' => 'sometimes|exists:wastes,id',
        'pocket_size_id' => 'sometimes|exists:pocket_sizes,id',
        'price' => 'sometimes|numeric',
        'effective_from' => 'sometimes|date',
        'effective_to' => 'nullable|date',
    ];
    protected array $filterable = [];
    protected array $sortable = ['id','effective_from'];
    protected ?string $permissionPrefix = 'waste_pricing';
}
