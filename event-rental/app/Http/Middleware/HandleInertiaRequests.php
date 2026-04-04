<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $workspace = $user?->currentWorkspaceRecord();
        $workspaceRole = $user?->currentWorkspaceRole();

        return [
            ...parent::share($request),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
            'app_url' => config('app.url'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_super_admin' => $user->isSuperAdmin(),
                    'workspace_role' => $workspaceRole,
                    'can_manage_workspace_users' => $user->canManageCurrentWorkspaceUsers(),
                ] : null,
            ],
            'workspace' => $workspace ? [
                'id' => $workspace->id,
                'name' => $workspace->company_name,
                'company_name' => $workspace->company_name,
                'vat_number' => $workspace->vat_number,
                'tic_number' => $workspace->tic_number,
                'address' => $workspace->address,
                'phone' => $workspace->phone,
                'email' => $workspace->email,
                'logo_url' => $workspace->logo_url,
                'brand_color' => $workspace->brand_color,
                'currency' => $workspace->currency,
                'invoice_prefix' => $workspace->invoice_prefix,
                'next_invoice_number' => $workspace->next_invoice_number,
                'quote_prefix' => $workspace->quote_prefix,
                'next_quote_number' => $workspace->next_quote_number,
                'iban' => $workspace->iban,
                'bic' => $workspace->bic,
                'current_user_role' => $workspaceRole,
                'can_manage_users' => $user?->canManageCurrentWorkspaceUsers() ?? false,
                'features' => $workspace->features()->pluck('is_enabled', 'feature_name'),
            ] : null,
        ];
    }
}
