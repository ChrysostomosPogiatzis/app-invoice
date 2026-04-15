<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffLeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_member_id',
        'start_date',
        'end_date',
        'days_count',
        'type',
        'status',
        'reason'
    ];

    public function staff()
    {
        return $this->belongsTo(StaffMember::class, 'staff_member_id');
    }
}
