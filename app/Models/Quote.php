<?php

namespace App\Models;

use App\Traits\BelongsToWorkspace;
use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace, HasAudit;

    protected $fillable = [
        'workspace_id',
        'contact_id',
        'quote_number',
        'date',
        'valid_until',
        'status',
        'discount',
        'subtotal_net',
        'total_vat_amount',
        'grand_total_gross',
        'notes',
        'terms',
        'converted_to_invoice_id',
        'converted_at',
    ];

    protected $appends = ['pdf_url'];

    public function getPdfUrlAttribute()
    {
        return url("/api/quotes/{$this->id}/download");
    }

    protected $casts = [
        'date' => 'date',
        'valid_until' => 'date',
        'converted_at' => 'datetime',
        'discount' => 'decimal:2',
        'subtotal_net' => 'decimal:2',
        'total_vat_amount' => 'decimal:2',
        'grand_total_gross' => 'decimal:2',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'converted_to_invoice_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'related_id')->where('related_type', 'quote');
    }
}