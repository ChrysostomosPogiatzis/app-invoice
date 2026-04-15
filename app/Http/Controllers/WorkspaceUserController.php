<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceUserController extends Controller
{
    /**
     * Display the current workspace team.
     */
    public function index(Request $request): Response
    {
        $workspace = $request->user()->currentWorkspaceRecord();

        abort_unless($workspace, 404);

        $users = $workspace->users()
            ->orderByRaw("CASE WHEN workspace_users.role = 'owner' THEN 0 WHEN workspace_users.role = 'admin' THEN 1 WHEN workspace_users.role = 'staff' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => $user->is_super_admin,
                'current_workspace_id' => $user->current_workspace_id,
                'role' => $user->pivot?->role,
            ]);

        return Inertia::render('WorkspaceUsers/Index', [
            'roles' => ['owner', 'admin', 'staff', 'viewer'],
            'workspaceUsers' => $users,
        ]);
    }

    /**
     * Add a user to the current workspace.
     */
    public function store(Request $request): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspaceRecord();

        abort_unless($workspace, 404);

        // Tier Enforcement for Authorities (Users)
        if (!\App\Services\TierService::canAddUser($workspace)) {
            $limits = \App\Services\TierService::getLimits($workspace->tier);
            return back()->withErrors([
                'email' => "Workspace authority limit reached for your {$workspace->tier} plan (Max: {$limits['max_users']} login accounts). Please upgrade to authorize more users."
            ]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in(['owner', 'admin', 'staff', 'viewer'])],
        ]);

        $existingUser = User::where('email', $validated['email'])->first();

        if (! $existingUser && empty($validated['password'])) {
            return back()->withErrors([
                'password' => 'A password is required when creating a brand-new user.',
            ]);
        }

        if ($existingUser && $workspace->users()->where('users.id', $existingUser->id)->exists()) {
            return back()->withErrors([
                'email' => 'That user is already part of this workspace.',
            ]);
        }

        DB::transaction(function () use ($validated, $existingUser, $workspace) {
            $user = $existingUser ?: User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'current_workspace_id' => $workspace->id,
            ]);

            if (! $user->current_workspace_id) {
                $user->update(['current_workspace_id' => $workspace->id]);
            }

            $workspace->users()->attach($user->id, ['role' => $validated['role']]);
        });

        return back()->with('success', 'Workspace user added successfully.');
    }

    /**
     * Update a user's workspace role.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspaceRecord();

        abort_unless($workspace, 404);
        abort_unless($workspace->users()->where('users.id', $user->id)->exists(), 404);

        $validated = $request->validate([
            'role' => ['required', Rule::in(['owner', 'admin', 'staff', 'viewer'])],
        ]);

        if ($user->id === $request->user()->id && ! in_array($validated['role'], ['owner', 'admin'], true)) {
            return back()->withErrors([
                'role' => 'You cannot downgrade your own access below workspace admin.',
            ]);
        }

        if ($this->isLastOwner($workspace->id, $user->id) && $validated['role'] !== 'owner') {
            return back()->withErrors([
                'role' => 'This workspace must keep at least one owner.',
            ]);
        }

        $workspace->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return back()->with('success', 'Workspace role updated.');
    }

    /**
     * Remove a user from the current workspace.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $workspace = $request->user()->currentWorkspaceRecord();

        abort_unless($workspace, 404);
        abort_unless($workspace->users()->where('users.id', $user->id)->exists(), 404);

        if ($user->id === $request->user()->id) {
            return back()->withErrors([
                'workspace' => 'You cannot remove yourself from the current workspace.',
            ]);
        }

        if ($this->isLastOwner($workspace->id, $user->id)) {
            return back()->withErrors([
                'workspace' => 'This workspace must keep at least one owner.',
            ]);
        }

        $workspace->users()->detach($user->id);

        return back()->with('success', 'User removed from workspace.');
    }

    protected function isLastOwner(int $workspaceId, int $userId): bool
    {
        $membership = DB::table('workspace_users')
            ->where('workspace_id', $workspaceId)
            ->where('user_id', $userId)
            ->first();

        if (! $membership || $membership->role !== 'owner') {
            return false;
        }

        $ownerCount = DB::table('workspace_users')
            ->where('workspace_id', $workspaceId)
            ->where('role', 'owner')
            ->count();

        return $ownerCount <= 1;
    }
}
