<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockService
{
    /**
     * Check if a product is available for a given date range.
     * 
     * Availability = (Current Stock in Warehouse) - (Total Booked for overlapping events)
     */
    public function isAvailable(int $productId, float $requestedQuantity): bool
    {
        $product = Product::findOrFail($productId);
        
        return $product->current_stock >= $requestedQuantity;
    }



    /**
     * Create a stock movement for an event.
     */
    public function recordMovement(int $productId, float $quantity, string $direction, string $type = 'out')
    {
        return StockMovement::create([
            'product_id' => $productId,
            'quantity' => $quantity,
            'direction' => $direction,
            'movement_type' => $type,
        ]);
    }
}
