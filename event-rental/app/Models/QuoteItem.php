<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'product_id',
        'description',
        'quantity',
        'unit_price_net',
        'vat_rate',
        'total_gross',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price_net' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'total_gross' => 'decimal:2',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}