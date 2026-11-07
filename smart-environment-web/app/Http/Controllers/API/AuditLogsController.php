<?php

namespace App\Http\Controllers\API;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogsController extends BaseResourceController
{
    protected string $modelClass = AuditLog::class;
    protected array $rulesStore = [];
    protected array $rulesUpdate = [];
    protected array $filterable = ['action','object_type','object_id'];
    protected array $sortable = ['id','created_at'];
    protected ?string $permissionPrefix = 'audit_log';

    public function store(Request $request)
    {
        abort(405);
    }

    public function update(Request $request, string $id)
    {
        abort(405);
    }

    public function destroy(string $id)
    {
        abort(405);
    }
}
