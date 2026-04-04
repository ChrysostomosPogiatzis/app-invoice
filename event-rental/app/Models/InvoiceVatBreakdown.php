<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceVatBreakdown extends Model
{
    use HasFactory;

    protected $table = 'invoice_vat_breakdown';

    protected $fillable = [
        'invoice_id', 'vat_rate', 'net_amount', 'vat_amount'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
