<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $fillable = ['contact_id', 'title', 'remind_at'];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
