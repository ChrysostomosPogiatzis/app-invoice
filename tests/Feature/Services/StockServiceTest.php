<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;
    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->stockService = new StockService();
        $this->workspace = Workspace::create(['company_name' => 'Test Company']);
    }

    public function test_product_availability_basic()
    {
        $product = Product::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Folding Chair',
            'current_stock' => 100,
            'sku' => 'CHAIR-01',
            'unit_price_net' => 10,
            'vat_rate' => 19,
        ]);

        $this->assertTrue($this->stockService->isAvailable($product->id, 50));
        $this->assertTrue($this->stockService->isAvailable($product->id, 100));
        $this->assertFalse($this->stockService->isAvailable($product->id, 101));
    }
}
