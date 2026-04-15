<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace, HasAudit;

    protected $fillable = [
        'workspace_id', 'name', 'sku', 'product_type', 'unit_price_gross',
        'vat_rate', 'current_stock', 'purchase_price', 'acquisition_date',
        'product_category_id'
    ];

    /**
     * Get the net unit price based on gross and VAT rate.
     */
    public function getUnitPriceNetAttribute()
    {
        return round($this->unit_price_gross / (1 + ($this->vat_rate / 100)), 4);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }



    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class);
    }
    
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
