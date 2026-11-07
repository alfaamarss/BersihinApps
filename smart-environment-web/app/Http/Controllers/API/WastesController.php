<?php

namespace App\Http\Controllers\API;

use App\Models\Waste;

class WastesController extends BaseResourceController
{
    protected string $modelClass = Waste::class;
    protected array $rulesStore = [
        'code' => 'nullable|string',
        'category' => 'required|in:organic,inorganic',
    ];
    protected array $rulesUpdate = [
        'code' => 'nullable|string',
        'category' => 'sometimes|in:organic,inorganic',
    ];
    protected array $filterable = ['code','category'];
    protected array $sortable = ['id','code'];
    protected ?string $permissionPrefix = 'waste';
}
