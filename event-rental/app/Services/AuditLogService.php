<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Log a sensitive action.
     */
    public function log(string $action, string $entityName, int $entityId, ?array $oldValues = null, ?array $newValues = null, ?int $workspaceId = null)
    {
        return AuditLog::create([
            'workspace_id' => $workspaceId ?? auth()->user()?->currentWorkspaceRecord()?->id,
            'user_id' => auth()->id(),
            'action_type' => $action,
            'entity_name' => $entityName,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
        ]);
    }
}
