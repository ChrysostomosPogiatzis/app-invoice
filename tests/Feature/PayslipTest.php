<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use App\Models\StaffMember;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayslipTest extends TestCase
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

    public function test_can_download_payslip_pdf()
    {
        $staff = StaffMember::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Demo Employee For PDF',
            'id_number' => '123'
        ]);

        $expense = Expense::create([
            'workspace_id' => $this->workspace->id,
            'staff_member_id' => $staff->id,
            'is_payroll' => true,
            'expense_date' => '2026-03-31',
            'gross_salary' => 2000,
            'net_payable' => 1700,
            'si_employee' => 150,
            'gesi_employee' => 50,
            'tax_employee' => 100,
            'title' => 'Payroll March 2026',
            'amount' => 2000,
            'category' => 'Payroll'
        ]);

        $response = $this->get(route('payslips.download', $expense->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
