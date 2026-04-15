<?php

namespace App\Models;

use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory, SoftDeletes, HasAudit;

    protected static function booted()
    {
        static::creating(function ($workspace) {
            if (empty($workspace->company_name)) {
                $workspace->company_name = 'Demo Testing Workspace';
            }
        });
    }

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
        'si_employee_rate',
        'si_employer_rate',
        'gesi_employee_rate',
        'gesi_employer_rate',
        'provident_employee_rate',
        'provident_employer_rate',
        'redundancy_rate',
        'training_rate',
        'cohesion_rate',
        'holiday_rate',
        'annual_tax_threshold',
        'tax_brackets',
        'is_active',
        'tier',
        'trial_ends_at',
        'last_billed_at',
    ];

    protected $casts = [
        'tax_brackets' => 'array',
        'trial_ends_at' => 'datetime',
        'last_billed_at' => 'datetime',
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

    public function subscriptionPayments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }
}
