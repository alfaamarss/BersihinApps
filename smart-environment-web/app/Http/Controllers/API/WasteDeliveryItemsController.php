<?php

namespace App\Http\Controllers\API;

use App\Models\WasteDeliveryItem;

class WasteDeliveryItemsController extends BaseResourceController
{
    protected string $modelClass = WasteDeliveryItem::class;
    protected array $rulesStore = [
        'waste_delivery_id' => 'required|exists:waste_deliveries,id',
        'waste_id' => 'required|exists:wastes,id',
        'pocket_size_id' => 'required|exists:pocket_sizes,id',
        'quantity' => 'required|integer|min:1',
        'unit_price' => 'required|numeric',
        'total_price' => 'required|numeric',
    ];
    protected array $rulesUpdate = [
        'waste_delivery_id' => 'sometimes|exists:waste_deliveries,id',
        'waste_id' => 'sometimes|exists:wastes,id',
        'pocket_size_id' => 'sometimes|exists:pocket_sizes,id',
        'quantity' => 'sometimes|integer|min:1',
        'unit_price' => 'sometimes|numeric',
        'total_price' => 'sometimes|numeric',
    ];
    protected array $filterable = [];
    protected array $sortable = ['id'];
    protected ?string $permissionPrefix = 'waste_delivery_item';
}
