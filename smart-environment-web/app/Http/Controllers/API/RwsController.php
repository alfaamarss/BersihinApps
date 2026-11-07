<?php

namespace App\Http\Controllers\API;

use App\Models\Rw;

class RwsController extends BaseResourceController
{
    protected string $modelClass = Rw::class;

    protected array $createRules = [
        'kelurahan_id' => 'required|exists:areas,id',
        'name' => 'required|string|max:255',
    ];

    protected array $updateRules = [
        'kelurahan_id' => 'sometimes|exists:areas,id',
        'name' => 'sometimes|string|max:255',
    ];

    protected array $searchable = ['name'];
    protected array $filterable = ['kelurahan_id'];
    protected array $sortable = ['id', 'name', 'kelurahan_id'];
    protected array $relations = ['kelurahan'];

    protected ?string $permissionPrefix = 'rw';
}
