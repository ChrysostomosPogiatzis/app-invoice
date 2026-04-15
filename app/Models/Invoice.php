<?php
namespace App\Models;

use App\Traits\BelongsToWorkspace;
use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkspace, HasAudit;

    protected $fillable = [
        'workspace_id',
        'contact_id',
        'invoice_number',
        'doc_type',
        'date',
        'due_date',
        'status',
        'currency',
        'exchange_rate',
        'parent_id',
        'subtotal_net',
        'total_vat_amount',
        'grand_total_gross',
        'amount_paid',
        'balance_due',
        'customer_signature_png',
        'customer_signature_name',
        'signature_timestamp',
        'signature_ip',
        'notes',
        'terms',
    ];

    protected $appends = ['pdf_url'];

    public function getPdfUrlAttribute()
    {
        return url("/api/invoices/{$this->id}/download");
    }

    public function parent()
    {
        return $this->belongsTo(Invoice::class, 'parent_id');
    }

    public function creditNotes()
    {
        return $this->hasMany(Invoice::class, 'parent_id')->where('doc_type', 'credit_note');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function vatBreakdown()
    {
        return $this->hasMany(InvoiceVatBreakdown::class);
    }



    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function publicShares()
    {
        return $this->hasMany(PublicShare::class);
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'related_id')->where('related_type', 'invoice');
    }

    public function bankTransactions()
    {
        return $this->morphMany(BankTransaction::class, 'linked');
    }
}
