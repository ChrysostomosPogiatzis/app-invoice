<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspaceFeature extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'workspace_id',
        'feature_name',
        'is_enabled',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
