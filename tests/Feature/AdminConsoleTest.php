<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_admin_console(): void
    {
        $workspace = Workspace::create([
            'company_name' => 'Northwind Events',
            'currency' => 'EUR',
        ]);

        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'current_workspace_id' => $workspace->id,
        ]);

        $superAdmin->workspaces()->attach($workspace->id, ['role' => 'owner']);

        $response = $this
            ->actingAs($superAdmin)
            ->get('/admin');

        $response
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Index')
                ->where('stats.users', 1)
                ->where('stats.super_admins', 1)
                ->where('workspaces.0.company_name', 'Northwind Events')
                ->where('users.0.is_super_admin', true)
            );
    }

    public function test_non_super_admin_cannot_view_admin_console(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/admin');

        $response->assertForbidden();
    }
}
