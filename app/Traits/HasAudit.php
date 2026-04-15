<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait HasAudit
{
    public static function bootHasAudit()
    {
        static::created(function ($model) {
            $model->logAudit('CREATE');
        });

        static::updated(function ($model) {
            $model->logAudit('UPDATE');
        });

        static::deleted(function ($model) {
            $model->logAudit('DELETE');
        });
    }

    protected function logAudit(string $action)
    {
        $user = Auth::user();
        if (!$user) return;

        // Skip if workspace_id is not present (some models might not have it yet)
        $workspaceId = $this->workspace_id ?? $user->currentWorkspaceRecord()?->id;
        if (!$workspaceId) return;

        $oldValues = $action === 'UPDATE' ? array_intersect_key($this->getRawOriginal(), $this->getDirty()) : null;
        $newValues = $action === 'DELETE' ? null : $this->getDirty();
        
        if ($action === 'CREATE') {
            $newValues = $this->getAttributes();
        }

        AuditLog::create([
            'workspace_id' => $workspaceId,
            'user_id' => $user->id,
            'action_type' => $action,
            'entity_name' => class_basename($this),
            'entity_id' => $this->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
        ]);
    }
}
