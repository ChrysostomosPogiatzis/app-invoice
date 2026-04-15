<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    protected $fillable = [
        'contact_id',
        'invoice_id',
        'call_type',
        'call_duration_seconds',
        'call_notes',
        'call_recording_url',
        'call_date',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
