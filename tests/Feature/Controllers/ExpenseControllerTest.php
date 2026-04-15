<?php

namespace Tests\Feature\Controllers;

use App\Models\Expense;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->workspace = Workspace::create([
            'name' => 'Demo Corp',
            'si_employee_rate' => 8.80,
            'si_employer_rate' => 12.00,
            'gesi_employee_rate' => 2.65,
            'gesi_employer_rate' => 2.90,
            'annual_tax_threshold' => 22000.00
        ]);
        $this->user->workspaces()->attach($this->workspace->id, ['role' => 'admin']);
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_create_a_standard_expense()
    {
        $response = $this->post(route('expenses.store'), [
            'category' => 'fuel',
            'vendor_name' => 'Shell',
            'amount' => 120.00, // Net
            'vat_amount' => 20.00,
            'expense_date' => '2026-04-06'
        ]);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'category' => 'fuel',
            'amount' => 120.00,
            'vat_amount' => 20.00,
            'is_payroll' => false
        ]);
    }

    /** @test */
    public function it_can_create_a_payroll_expense()
    {
        $response = $this->post(route('expenses.store'), [
            'category' => 'staff_wages',
            'vendor_name' => 'Andreas Georgiou',
            'amount' => 2500.00, // Gross
            'gross_salary' => 2500.00,
            'expense_date' => '2026-04-06',
            'si_employee' => 220.00,
            'si_employer' => 300.00,
            'gesi_employee' => 66.25,
            'gesi_employer' => 72.50,
            'tax_employee' => 76.08,
            'net_payable' => 2137.67,
            'is_payroll' => true
        ]);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'category' => 'staff_wages',
            'is_payroll' => true,
            'gross_salary' => 2500.00,
            'amount' => 2500.00
        ]);
    }
}
