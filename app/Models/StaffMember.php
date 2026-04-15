<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffMember extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace, HasAudit;

    protected $fillable = [
        'workspace_id',
        'name',
        'email',
        'phone',
        'position',
        'base_salary',
        'provident_employee_rate',
        'provident_employer_rate',
        'union_rate',
        'union_type',
        'use_holiday_fund',
        'holiday_rate',
        'id_number',
        'si_number',
        'tax_id',
        'iban',
        'joining_date',
        'emergency_contact_name',
        'emergency_contact_phone',
        'annual_leave_total',
        'leave_balance'
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function documents()
    {
        return $this->hasMany(StaffDocument::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(StaffLeaveRequest::class);
    }
}
