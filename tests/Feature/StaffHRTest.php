<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\StaffMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffHRTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->workspace = Workspace::create(['name' => 'Demo Workspace', 'company_name' => 'Demo Company', 'owner_id' => $this->user->id]);
        $this->user->workspaces()->attach($this->workspace->id, ['role' => 'admin']);
        $this->actingAs($this->user);
    }

    public function test_can_create_staff_member_with_hr_fields()
    {
        $response = $this->post(route('staff-members.store'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '99000000',
            'position' => 'Senior Developer',
            'base_salary' => 5000,
            'si_number' => 'SI123456',
            'tax_id' => 'TAX789',
            'iban' => 'CY001122334455667788',
            'joining_date' => '2026-01-01',
            'annual_leave_total' => 20,
            'leave_balance' => 20
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('staff_members', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'si_number' => 'SI123456'
        ]);
    }

    public function test_can_view_staff_profile_with_relations()
    {
        $staff = StaffMember::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Jane Smith'
        ]);

        // Add a payroll expense
        $staff->expenses()->create([
            'workspace_id' => $this->workspace->id,
            'title' => 'Payroll Jan 2026',
            'amount' => 3000,
            'expense_date' => '2026-01-31',
            'category' => 'Payroll',
            'is_payroll' => true,
            'gross_salary' => 3000,
            'net_payable' => 2500,
            'si_employee' => 250,
            'gesi_employee' => 80,
            'tax_employee' => 170
        ]);

        $response = $this->get(route('staff-members.show', $staff->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Finance/Staff/Show')
            ->has('member.expenses', 1)
        );
    }

    public function test_validation_prevents_duplicate_logic_if_needed()
    {
        // For now, names aren't unique across workspace, but we handle it
        $this->post(route('staff-members.store'), [
            'name' => '', // Should fail
        ])->assertSessionHasErrors('name');
    }
}
