<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'share_token', 'password', 'expires_at', 'view_count', 'last_viewed_at'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
