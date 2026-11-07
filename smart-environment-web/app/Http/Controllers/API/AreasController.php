<?php

namespace App\Http\Controllers\API;

use App\Models\Area;

class AreasController extends BaseResourceController
{
    protected string $modelClass = Area::class;
    protected array $rulesStore = [
        'parent_id' => 'nullable|exists:areas,id',
        'name' => 'required|string',
        'level' => 'required|in:Kecamatan,Kelurahan',
    ];
    protected array $rulesUpdate = [
        'parent_id' => 'nullable|exists:areas,id',
        'name' => 'sometimes|string',
        'level' => 'sometimes|in:Kecamatan,Kelurahan',
    ];
    protected array $filterable = ['name','level'];
    protected array $sortable = ['id','name'];
    protected ?string $permissionPrefix = 'area';
}
