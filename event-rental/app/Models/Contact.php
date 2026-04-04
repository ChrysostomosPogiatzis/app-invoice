<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace, HasAudit;

    protected $fillable = [
        'workspace_id', 'name', 'company_name', 'email', 'mobile_number', 'vat_number', 'address', 'contact_type', 'general_info'
    ];

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }



    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function communications()
    {
        return $this->hasMany(CallLog::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }
}
