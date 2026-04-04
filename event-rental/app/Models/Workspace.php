<?php

namespace App\Models;

use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory, SoftDeletes, HasAudit;

    protected $fillable = [
        'company_name',
        'vat_number',
        'tic_number',
        'address',
        'phone',
        'email',
        'iban',
        'bic',
        'logo_url',
        'brand_color',
        'currency',
        'invoice_prefix',
        'next_invoice_number',
        'default_invoice_notes',
        'quote_prefix',
        'next_quote_number',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'workspace_users')
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }



    public function features()
    {
        return $this->hasMany(WorkspaceFeature::class);
    }

    public function featureEnabled($featureName)
    {
        return $this->features()->where('feature_name', $featureName)->where('is_enabled', true)->exists();
    }
}
