<?php

namespace App\Http\Controllers\API;

use App\Models\Payment;

class PaymentsController extends BaseResourceController
{
    protected string $modelClass = Payment::class;
    protected array $rulesStore = [
        'invoice_id' => 'required|exists:invoices,id',
        'amount' => 'required|numeric',
        'payment_method' => 'required|string',
        'provider_transaction_id' => 'nullable|string',
    ];
    protected array $rulesUpdate = [
        'invoice_id' => 'sometimes|exists:invoices,id',
        'amount' => 'sometimes|numeric',
        'payment_method' => 'sometimes|string',
        'provider_transaction_id' => 'nullable|string',
    ];
    protected array $filterable = [];
    protected array $sortable = ['id','created_at'];
    protected ?string $permissionPrefix = 'payment';
}
