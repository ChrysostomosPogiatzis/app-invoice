<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_member_id',
        'name',
        'file_path',
        'type',
        'expiry_date'
    ];

    public function staff()
    {
        return $this->belongsTo(StaffMember::class);
    }
}
