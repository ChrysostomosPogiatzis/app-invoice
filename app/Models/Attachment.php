<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $fillable = [
        'related_id',
        'related_type',
        'file_url',
        'file_name',
    ];

    public function related()
    {
        return $this->morphTo(null, 'related_type', 'related_id');
    }
}
