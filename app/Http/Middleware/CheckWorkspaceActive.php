<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckWorkspaceActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        
        // Always allow logout, workspace switching, and billing checkout regardless of node status
        if ($request->routeIs(['logout', 'workspaces.switch', 'billing.checkout'])) {
            return $next($request);
        }
        
        if ($user && !$user->is_super_admin) {
            $workspace = $user->currentWorkspaceRecord();
            
            if ($workspace && !$workspace->is_active) {
                // If it's an Inertia request, we should probably redirect to a specific "Account Suspended" page
                // For now, let's just abort or redirect to a landing page with a message
                return Inertia::render('Errors/Suspended', [
                    'message' => 'This business workspace is currently inactive. Please contact support or resolve any outstanding payments.'
                ]);
            }
        }

        return $next($request);
    }
}
