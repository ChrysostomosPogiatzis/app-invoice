<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'workspace_id',
        'amount',
        'payment_method',
        'gateway_order_id',
        'billed_at',
        'extended_until',
        'notes'
    ];

    protected $casts = [
        'billed_at' => 'datetime',
        'extended_until' => 'datetime',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
