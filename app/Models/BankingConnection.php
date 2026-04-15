<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class BankingConnection extends Model
{
    protected $fillable = [
        'workspace_id',
        'provider',
        'label',
        'is_active',
        'credentials',      // stored encrypted, decoded as array
        'last_synced_at',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    // Encrypt the credentials JSON when saving
    public function setCredentialsAttribute(array|string $value): void
    {
        $json = is_array($value) ? json_encode($value) : $value;
        $this->attributes['credentials'] = Crypt::encryptString($json);
    }

    // Decrypt and decode credentials when reading
    public function getCredentialsAttribute(string $value): array
    {
        return json_decode(Crypt::decryptString($value), true) ?? [];
    }

    /**
     * Get a specific credential key safely.
     */
    public function credential(string $key, mixed $default = null): mixed
    {
        return $this->credentials[$key] ?? $default;
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }

    public function transactions()
    {
        return $this->hasMany(BankTransaction::class);
    }
}
