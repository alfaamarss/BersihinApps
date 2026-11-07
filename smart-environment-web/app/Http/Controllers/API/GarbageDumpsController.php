<?php

namespace App\Http\Controllers\API;

use App\Models\GarbageDump;

class GarbageDumpsController extends BaseResourceController
{
    protected string $modelClass = GarbageDump::class;
    protected array $rulesStore = [
        'name' => 'required|string',
        'address' => 'required|string',
        'capacity' => 'nullable|numeric',
    ];
    protected array $rulesUpdate = [
        'name' => 'sometimes|string',
        'address' => 'sometimes|string',
        'capacity' => 'nullable|numeric',
    ];
    protected array $filterable = ['name'];
    protected array $sortable = ['id','name'];
    protected ?string $permissionPrefix = 'garbage_dump';
}
