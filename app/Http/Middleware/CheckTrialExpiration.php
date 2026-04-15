<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TierService;
use App\Http\Controllers\Concerns\ResolvesWorkspace;
use Symfony\Component\HttpFoundation\Response;

class CheckTrialExpiration
{
    use ResolvesWorkspace;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if (!$user || $user->isSuperAdmin()) {
            return $next($request);
        }

        $workspace = auth()->user()?->currentWorkspaceRecord();
        $isInactive = $workspace && (bool)$workspace->is_active === false;
        $isExpired = $workspace && TierService::isTrialExpired($workspace);

        if ($isInactive || $isExpired) {
            // Allow access to the settings so they can upgrade
            // Or allow admins to still manage the workspace
            $allowedRoutes = ['settings.edit', 'settings.update', 'admin.*', 'logout', 'billing.checkout', 'billing.success', 'billing.cancel', 'workspaces.switch'];
            
            if (!$request->routeIs($allowedRoutes)) {
                $errorMsg = $isInactive 
                    ? 'Business Suspended: This node has been manually locked by administration. Please contact support.' 
                    : 'Your 7-day trial has expired. Please upgrade your node or contact administration.';
                
                return redirect()->route('settings.edit')->with('error', $errorMsg);
            }
        }

        return $next($request);
    }
}
