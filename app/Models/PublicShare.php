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

    protected $casts = [
        'expires_at' => 'datetime',
        'last_viewed_at' => 'datetime',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function requiresPassword(): bool
    {
        return filled($this->password);
    }
}
