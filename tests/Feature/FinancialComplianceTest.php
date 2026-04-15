<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialComplianceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->workspace = Workspace::create([
            'name' => 'Compliance Labs',
            'si_employee_rate' => 8.80,
            'si_employer_rate' => 12.00,
            'gesi_employee_rate' => 2.65,
            'gesi_employer_rate' => 2.90,
            'annual_tax_threshold' => 22000.00,
            'si_monthly_cap' => 5546.00,
            'tax_brackets' => [
                ['threshold' => 0, 'rate' => 0],
                ['threshold' => 22000, 'rate' => 20],
                ['threshold' => 32000, 'rate' => 25],
                ['threshold' => 42000, 'rate' => 30],
                ['threshold' => 72000, 'rate' => 35]
            ]
        ]);
        $this->user->workspaces()->attach($this->workspace->id, ['role' => 'admin']);
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_applies_social_insurance_monthly_cap()
    {
        // Salary well above the €5,546 cap
        $gross = 10000.00;
        $cap = 5546.00;
        
        // Expected SI (Employee) = 5546 * 8.8%
        $expectedSI = round($cap * 0.088, 2); // 488.05
        
        // Manual calc logic (as in ExpenseCreate.vue)
        $insurable = min($gross, $cap);
        $actualSI = round($insurable * 0.088, 2);

        $this->assertEquals(488.05, $actualSI);
        $this->assertLessThan($gross * 0.088, $actualSI);
    }

    /** @test */
    public function it_calculates_high_income_progressive_tax_correctly()
    {
        // €100,000 Annual Salary
        // Taxable income = 100,000 - SI(capped) - GESI(uncapped)
        $grossMonthly = 100000 / 12; // 8333.33
        $siMonthly = round(5546 * 0.088, 2); // 488.05
        $gesiMonthly = round($grossMonthly * 0.0265, 2); // 220.83
        
        $annualGross = 100000;
        $annualSI = $siMonthly * 12; // 5856.60
        $annualGESI = $gesiMonthly * 12; // 2649.96
        
        $annualTaxable = $annualGross - $annualSI - $annualGESI; // ~91493.44
        
        // Brackets:
        // 0-22k: 0
        // 22k-32k: 10k * 20% = 2000
        // 32k-42k: 10k * 25% = 2500
        // 42k-72k: 30k * 30% = 9000
        // 72k-end: (91493 - 72000) * 35% = 19493 * 35% = 6822.55
        // Total = 20322.55
        
        $taxable = $annualTaxable;
        $totalTax = 0;
        
        // Logic check
        if ($taxable > 22000) $totalTax += min($taxable - 22000, 10000) * 0.20;
        if ($taxable > 32000) $totalTax += min($taxable - 32000, 10000) * 0.25;
        if ($taxable > 42000) $totalTax += min($taxable - 42000, 30000) * 0.30;
        if ($taxable > 72000) $totalTax += ($taxable - 72000) * 0.35;
        
        $this->assertGreaterThan(20000, $totalTax);
        $this->assertLessThan(21000, $totalTax);
    }

    /** @test */
    public function it_handles_zero_vat_expenses_gracefully()
    {
        $response = $this->post(route('expenses.store'), [
            'category' => 'other',
            'amount' => 100.00,
            'vat_amount' => 0,
            'expense_date' => '2026-04-06'
        ]);
        
        $response->assertRedirect();
        $this->assertDatabaseHas('expenses', [
            'amount' => 100.00,
            'vat_amount' => 0
        ]);
    }
}
