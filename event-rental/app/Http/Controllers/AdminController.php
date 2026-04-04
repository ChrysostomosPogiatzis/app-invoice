<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Display the platform administration dashboard.
     */
    public function index(): Response
    {
        $workspaces = Workspace::query()
            ->withCount(['users', 'products'])
            ->orderBy('company_name')
            ->get()
            ->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'company_name' => $workspace->company_name,
                'email' => $workspace->email,
                'currency' => $workspace->currency,
                'users_count' => $workspace->users_count,
                'products_count' => $workspace->products_count,
                'created_at' => optional($workspace->created_at)->toDateString(),
            ]);

        $users = User::query()
            ->with(['workspaces' => fn ($query) => $query->orderBy('company_name')])
            ->orderByDesc('is_super_admin')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => $user->is_super_admin,
                'current_workspace_id' => $user->current_workspace_id,
                'workspaces' => $user->workspaces->map(fn (Workspace $workspace) => [
                    'id' => $workspace->id,
                    'company_name' => $workspace->company_name,
                    'role' => $workspace->pivot?->role,
                ])->values(),
            ]);

        return Inertia::render('Admin/Index', [
            'stats' => [
                'users' => $users->count(),
                'super_admins' => $users->where('is_super_admin', true)->count(),
                'workspaces' => $workspaces->count(),
            ],
            'users' => $users,
            'workspaces' => $workspaces,
        ]);
    }
}
