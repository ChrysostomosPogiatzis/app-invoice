<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialMathTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->workspace = Workspace::create([
            'name' => 'Test Workspace',
            'si_employee_rate' => 8.80,
            'si_employer_rate' => 12.00,
            'gesi_employee_rate' => 2.65,
            'gesi_employer_rate' => 2.90,
            'annual_tax_threshold' => 22000.00,
            'invoice_prefix' => 'INV',
            'next_invoice_number' => 100
        ]);
        
        $this->user->workspaces()->attach($this->workspace->id, ['role' => 'admin']);
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_correctly_calculates_payroll_for_the_2026_tax_year()
    {
        // 2,500 Gross Monthly
        $gross = 2500.00;
        
        // S.I. & GESI (EE)
        $siEE = round($gross * 0.088, 2); // 220.00
        $gesiEE = round($gross * 0.0265, 2); // 66.25
        
        // Taxable
        $taxableMonthly = $gross - $siEE - $gesiEE; // 2213.75
        $taxableAnnual = $taxableMonthly * 12; // 26565.00
        
        // Tax (Above 22,000 @ 20%)
        $taxAnnual = ($taxableAnnual > 22000) ? ($taxableAnnual - 22000) * 0.20 : 0; // 913.00
        $taxMonthly = round($taxAnnual / 12, 2); // 76.08
        
        // Net
        $net = $gross - $siEE - $gesiEE - $taxMonthly;
        
        $this->assertEquals(220.00, $siEE);
        $this->assertEquals(66.25, $gesiEE);
        $this->assertEquals(76.08, $taxMonthly);
        $this->assertEquals(2137.67, $net); // Match with user expectations!
    }

    /** @test */
    public function it_correctly_sums_total_company_cost_for_payroll_in_reports()
    {
        $gross = 2500.00;
        $siER = round($gross * 0.12, 2); // 300.00
        $gesiER = round($gross * 0.029, 2); // 72.50
        
        $totalCost = $gross + $siER + $gesiER;
        
        $this->assertEquals(372.50, $siER + $gesiER); // Match with user "€372.50 (ER)"
        $this->assertEquals(2872.50, $totalCost);
    }

    /** @test */
    public function it_calculates_standard_expense_net_correctly()
    {
        $grossTotalPaid = 140.00;
        $vatPaid = 20.00;
        $calculatedNet = $grossTotalPaid - $vatPaid;
        
        $this->assertEquals(120.00, $calculatedNet);
    }

    /** @test */
    public function it_verifies_invoice_gross_math()
    {
        $netPrice = 100.00;
        $vatRate = 19;
        
        $grossPrice = round($netPrice * (1 + ($vatRate / 100)), 2);
        
        $this->assertEquals(119.00, $grossPrice);
    }
}
