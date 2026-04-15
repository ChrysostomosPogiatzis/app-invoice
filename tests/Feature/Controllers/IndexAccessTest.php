<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexAccessTest extends TestCase
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
    public function dashboard_is_accessible() { $this->get(route('dashboard'))->assertOk(); }
    
    /** @test */
    public function expenses_is_accessible() { $this->get(route('expenses.index'))->assertOk(); }
    
    /** @test */
    public function invoices_is_accessible() { $this->get(route('invoices.index'))->assertOk(); }
    
    /** @test */
    public function quotes_is_accessible() { $this->get(route('quotes.index'))->assertOk(); }
    
    /** @test */
    public function contacts_is_accessible() { $this->get(route('contacts.index'))->assertOk(); }
    
    /** @test */
    public function products_is_accessible() { $this->get(route('products.index'))->assertOk(); }
    
    /** @test */
    public function reports_is_accessible() { $this->get(route('reports.index'))->assertOk(); }
    
    /** @test */
    public function banking_is_accessible() { $this->get(route('banking.index'))->assertOk(); }
    
    /** @test */
    public function staff_members_is_accessible() { $this->get(route('staff-members.index'))->assertOk(); }
    
    /** @test */
    public function workspace_users_is_accessible() { $this->get(route('workspace-users.index'))->assertOk(); }

    /** @test */
    public function settings_is_accessible() { $this->get(route('settings.edit'))->assertOk(); }
}
