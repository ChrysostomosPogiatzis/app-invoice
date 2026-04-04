<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class WorkspaceUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_admin_can_view_team_management_page(): void
    {
        [$workspace, $admin] = $this->createWorkspaceUser('admin');

        $response = $this
            ->actingAs($admin)
            ->get('/workspace-users');

        $response
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('WorkspaceUsers/Index')
                ->where('workspaceUsers.0.email', $admin->email)
                ->where('workspaceUsers.0.role', 'admin')
            );
    }

    public function test_viewer_cannot_view_team_management_page(): void
    {
        [, $viewer] = $this->createWorkspaceUser('viewer');

        $response = $this
            ->actingAs($viewer)
            ->get('/workspace-users');

        $response->assertForbidden();
    }

    public function test_workspace_admin_can_add_a_new_user_with_role(): void
    {
        [$workspace, $admin] = $this->createWorkspaceUser('admin');

        $response = $this
            ->actingAs($admin)
            ->post('/workspace-users', [
                'name' => 'New Coordinator',
                'email' => 'coordinator@example.com',
                'password' => 'secret123',
                'role' => 'staff',
            ]);

        $response->assertRedirect();

        $user = User::where('email', 'coordinator@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame($workspace->id, $user->current_workspace_id);
        $this->assertDatabaseHas('workspace_users', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role' => 'staff',
        ]);
    }

    public function test_workspace_admin_can_update_member_role(): void
    {
        [$workspace, $admin] = $this->createWorkspaceUser('admin');
        [, $member] = $this->createWorkspaceUser('staff', $workspace);

        $response = $this
            ->actingAs($admin)
            ->patch("/workspace-users/{$member->id}", [
                'role' => 'viewer',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('workspace_users', [
            'workspace_id' => $workspace->id,
            'user_id' => $member->id,
            'role' => 'viewer',
        ]);
    }

    private function createWorkspaceUser(string $role, ?Workspace $workspace = null): array
    {
        $workspace ??= Workspace::create([
            'company_name' => 'Workspace Test Company',
            'currency' => 'EUR',
        ]);

        $user = User::factory()->create([
            'current_workspace_id' => $workspace->id,
        ]);

        $workspace->users()->attach($user->id, ['role' => $role]);

        return [$workspace, $user];
    }
}
