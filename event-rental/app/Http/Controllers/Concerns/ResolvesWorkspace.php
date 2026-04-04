<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Workspace;

trait ResolvesWorkspace
{
    protected function currentWorkspace(): Workspace
    {
        $workspace = auth()->user()?->currentWorkspaceRecord();

        abort_unless($workspace, 403, 'No active workspace is available for this account.');

        return $workspace;
    }

    protected function currentWorkspaceId(): int
    {
        return $this->currentWorkspace()->id;
    }
}
