<?php

namespace App\Http\Controllers\API;

use App\Models\PocketSize;

class PocketSizesController extends BaseResourceController
{
    protected string $modelClass = PocketSize::class;
    protected array $rulesStore = [
        'name' => 'required|string|unique:pocket_sizes,name',
        'description' => 'nullable|string',
    ];
    protected array $rulesUpdate = [
        'name' => 'sometimes|string|unique:pocket_sizes,name',
        'description' => 'nullable|string',
    ];
    protected array $filterable = ['name'];
    protected array $sortable = ['id','name'];
    protected ?string $permissionPrefix = 'pocket_size';
}
