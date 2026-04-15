<?php

namespace App\Traits;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToWorkspace
{
    public static function bootBelongsToWorkspace()
    {
        static::creating(function (Model $model) {
            if (! $model->workspace_id && auth()->check()) {
                $model->workspace_id = auth()->user()->currentWorkspaceRecord()?->id;
            }
        });

        static::addGlobalScope('workspace', function (Builder $builder) {
            if (auth()->check()) {
                $workspaceId = auth()->user()->currentWorkspaceRecord()?->id;

                if ($workspaceId) {
                    $builder->where('workspace_id', $workspaceId);
                }
            }
        });
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
