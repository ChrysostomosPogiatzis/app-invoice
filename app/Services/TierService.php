<?php

namespace App\Services;

use App\Models\Workspace;

class TierService
{
    const TIER_STARTER = 'starter';
    const TIER_PROFESSIONAL = 'professional';
    const TIER_ENTERPRISE = 'enterprise';

    const PRICE_STARTER = 20.00;
    const PRICE_PROFESSIONAL = 50.00;
    const PRICE_ENTERPRISE = 200.00;

    /**
     * Get limits for each tier
     */
    public static function getLimits(string $tier)
    {
        $config = [
            self::TIER_STARTER => [
                'max_staff' => 1,
                'max_users' => 1,
                'max_tokens' => 0,
                'features' => ['crm', 'invoicing', 'basic_banking'],
            ],
            self::TIER_PROFESSIONAL => [
                'max_staff' => 5,
                'max_users' => 3,
                'max_tokens' => 2,
                'features' => ['crm', 'invoicing', 'multi_currency_banking', 'expenses', 'crm_reminders', 'api_access'],
            ],
            self::TIER_ENTERPRISE => [
                'max_staff' => 999999, // Unlimited
                'max_users' => 99,
                'max_tokens' => 999,
                'features' => ['crm', 'invoicing', 'multi_currency_banking', 'expenses', 'crm_reminders', 'mt940_import', 'audit_logs', 'api_access'],
            ],
        ];

        return $config[$tier] ?? $config[self::TIER_STARTER];
    }

    /**
     * Get price for a tier
     */
    public static function getPrice(string $tier): float
    {
        return match ($tier) {
            self::TIER_PROFESSIONAL => self::PRICE_PROFESSIONAL,
            self::TIER_ENTERPRISE => self::PRICE_ENTERPRISE,
            default => self::PRICE_STARTER,
        };
    }

    /**
     * Check if a workspace can generate more API tokens
     */
    public static function canGenerateToken(Workspace $workspace): bool
    {
        $limits = self::getLimits($workspace->tier);
        if (!in_array('api_access', $limits['features'])) return false;

        $tokenCount = \DB::table('personal_access_tokens')
            ->where('tokenable_type', 'App\Models\User')
            ->whereIn('tokenable_id', $workspace->users()->pluck('users.id'))
            ->count();
        
        return $tokenCount < $limits['max_tokens'];
    }

    /**
     * Check if a workspace can add more staff
     */
    public static function canAddStaff(Workspace $workspace): bool
    {
        $limits = self::getLimits($workspace->tier);
        $currentStaffCount = \App\Models\StaffMember::where('workspace_id', $workspace->id)->count();
        return $currentStaffCount < $limits['max_staff'];
    }

    /**
     * Check if a workspace can add more users (Authorities)
     */
    public static function canAddUser(Workspace $workspace): bool
    {
        $limits = self::getLimits($workspace->tier);
        $currentUserCount = $workspace->users()->count();
        return $currentUserCount < $limits['max_users'];
    }

    /**
     * Check if a feature is available for a tier
     */
    public static function isFeatureAvailable(string $tier, string $feature): bool
    {
        $limits = self::getLimits($tier);
        return in_array($feature, $limits['features']);
    }

    /**
     * Check if a workspace trial has expired
     */
    public static function isTrialExpired($workspace): bool
    {
        // Enterprise accounts never "expire" in the same way
        if ($workspace->tier === self::TIER_ENTERPRISE) return false;

        // If no trial date is set, we assume it's manually activated
        if (!$workspace->trial_ends_at) return false;

        return \Carbon\Carbon::parse($workspace->trial_ends_at)->isPast();
    }

    /**
     * Get remaining days in trial
     */
    public static function getTrialRemainingDays($workspace): int
    {
        if (!$workspace->trial_ends_at) return 999;
        return (int) max(0, \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($workspace->trial_ends_at), false));
    }
}
