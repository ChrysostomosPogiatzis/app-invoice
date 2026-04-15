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
        'staff_member_id',
        'is_payroll',
        'gross_salary',
        'si_employee',
        'si_employer',
        'gesi_employee',
        'gesi_employer',
        'tax_employee',
        'provident_employee',
        'provident_employer',
        'redundancy_amount',
        'training_amount',
        'cohesion_amount',
        'holiday_amount',
        'union_amount',
        'net_payable',
        'receipt_url',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function staffMember()
    {
        return $this->belongsTo(StaffMember::class);
    }



    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'related_id')->where('related_type', 'expense');
    }

    public function bankTransactions()
    {
        return $this->morphMany(BankTransaction::class, 'linked');
    }
}
