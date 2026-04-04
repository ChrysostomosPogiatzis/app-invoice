<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use BelongsToWorkspace, HasAudit;

    protected $fillable = [
        'workspace_id',

        'category',
        'amount',
        'vat_amount',
        'expense_date',
        'reminder_time',
        'vendor_name',
        'receipt_url',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }



    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'related_id')->where('related_type', 'expense');
    }
}
