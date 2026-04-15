<?php

namespace Tests\Feature\Controllers;

use App\Models\Expense;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
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
    public function it_can_generate_a_monthly_payroll_pdf()
    {
        $response = $this->get(route('reports.monthly.payroll', ['month' => '2026-04']));
        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    /** @test */
    public function it_can_generate_an_accountant_export_zip()
    {
        $response = $this->get(route('reports.accountant.export', [
            'date_from' => '2026-04-01',
            'date_to' => '2026-04-30'
        ]));
        
        $response->assertOk();
        $response->assertHeader('content-type', 'application/zip');
    }
}
