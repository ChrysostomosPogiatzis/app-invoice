<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    protected $fillable = [
        'workspace_id',
        'banking_connection_id',
        'external_id',
        'provider',
        'transaction_date',
        'type',
        'amount',
        'currency',
        'status',
        'card_type',
        'card_last4',
        'reference',
        'description',
        'raw_payload',
        'linked_type',
        'linked_id',
    ];

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount'           => 'decimal:2',
        'raw_payload'      => 'array',
    ];

    public function connection()
    {
        return $this->belongsTo(BankingConnection::class, 'banking_connection_id');
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Polymorphic link — resolves to Invoice or Expense.
     */
    public function linked()
    {
        return $this->morphTo(__FUNCTION__, 'linked_type', 'linked_id');
    }

    /**
     * Convenience: is this transaction reconciled?
     */
    public function isReconciled(): bool
    {
        return ! is_null($this->linked_type);
    }
}
