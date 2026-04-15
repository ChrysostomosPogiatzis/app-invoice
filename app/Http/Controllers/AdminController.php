<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * Display the platform administration dashboard.
     */
    public function index(): Response
    {
        $workspaces = Workspace::query()
            ->with(['users' => fn($q) => $q->wherePivot('role', 'admin')])
            ->with(['subscriptionPayments' => fn($q) => $q->latest()->limit(20)])
            ->withCount(['users', 'products'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'company_name' => $workspace->company_name,
                'email' => $workspace->email,
                'currency' => $workspace->currency,
                'is_active' => (bool)$workspace->is_active,
                'tier' => $workspace->tier ?? 'starter',
                'owner_id' => $workspace->users->first()?->id,
                'users_count' => $workspace->users_count,
                'products_count' => $workspace->products_count,
                'trial_ends_at' => $workspace->trial_ends_at ? $workspace->trial_ends_at->toDateString() : null,
                'last_billed_at' => $workspace->last_billed_at ? $workspace->last_billed_at->toDateString() : null,
                'trial_expired' => \App\Services\TierService::isTrialExpired($workspace),
                'created_at' => optional($workspace->created_at)->toDateString(),
                'payments' => $workspace->subscriptionPayments,
            ]);

        $users = User::query()
            ->with(['workspaces' => fn ($query) => $query->orderBy('company_name')])
            ->orderByDesc('is_super_admin')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => $user->is_super_admin,
                'current_workspace_id' => $user->current_workspace_id,
                'workspaces' => $user->workspaces->map(fn (Workspace $workspace) => [
                    'id' => $workspace->id,
                    'company_name' => $workspace->company_name,
                    'role' => $workspace->pivot?->role,
                ])->values(),
            ]);

        $totalRevenue = \App\Models\SubscriptionPayment::sum('amount') ?: 0;
        $monthlyRevenue = \App\Models\SubscriptionPayment::where('billed_at', '>=', now()->startOfMonth())->sum('amount') ?: 0;

        return Inertia::render('Admin/Index', [
            'stats' => [
                'users' => $users->count(),
                'super_admins' => $users->where('is_super_admin', true)->count(),
                'workspaces' => $workspaces->count(),
                'total_revenue' => (float)$totalRevenue,
                'monthly_revenue' => (float)$monthlyRevenue,
            ],
            'users' => $users,
            'workspaces' => $workspaces,
        ]);
    }

    /**
     * Store a newly created workspace in storage.
     */
    public function storeWorkspace(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'currency' => 'required|string|max:3',
            'tier' => 'required|string|in:starter,professional,enterprise',
            'owner_id' => 'required|exists:users,id',
        ]);

        $workspace = Workspace::create([
            'company_name' => $validated['company_name'],
            'email' => $validated['email'],
            'currency' => $validated['currency'],
            'tier' => $validated['tier'],
            'invoice_prefix' => 'INV-',
            'quote_prefix' => 'QT-',
            'next_invoice_number' => 1001,
            'next_quote_number' => 1001,
            'si_employee_rate' => 8.8,
            'si_employer_rate' => 8.8,
            'gesi_employee_rate' => 2.65,
            'gesi_employer_rate' => 2.9,
            'si_monthly_cap' => 5546,
            'trial_ends_at' => now()->addDays(7), // Default 7-day trial
            'tax_brackets' => [
                ['threshold' => 0, 'rate' => 0],
                ['threshold' => 19500, 'rate' => 20],
                ['threshold' => 28000, 'rate' => 25],
                ['threshold' => 36300, 'rate' => 30],
                ['threshold' => 60000, 'rate' => 35],
            ]
        ]);

        $user = User::find($validated['owner_id']);
        $user->workspaces()->attach($workspace->id, ['role' => 'admin']);
        
        if (!$user->current_workspace_id) {
            $user->update(['current_workspace_id' => $workspace->id]);
        }

        return redirect()->route('admin.index')->with('success', 'Workspace created and owner assigned.');
    }

    /**
     * Update the specified workspace.
     */
    public function updateWorkspace(Request $request, $id)
    {
        $workspace = Workspace::findOrFail($id);

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'currency' => 'required|string|max:3',
            'tier' => 'required|string|in:starter,professional,enterprise',
            'owner_id' => 'nullable|exists:users,id',
        ]);

        $workspace->update([
            'company_name' => $validated['company_name'],
            'email' => $validated['email'],
            'currency' => $validated['currency'],
            'tier' => $validated['tier'],
            'trial_ends_at' => $request->input('trial_ends_at'),
        ]);

        // Auto-clear trial if upgrading to enterprise
        if ($validated['tier'] === 'enterprise') {
            $workspace->update(['trial_ends_at' => null]);
        }

        if ($validated['owner_id']) {
            // Check if owner actually changed
            $currentOwner = $workspace->users()->wherePivot('role', 'admin')->first();
            if (!$currentOwner || $currentOwner->id != $validated['owner_id']) {
                // Demote old admin if exists
                $workspace->users()->wherePivot('role', 'admin')->updateExistingPivot($currentOwner->id ?? 0, ['role' => 'user']);
                
                // Promote or Attach new admin
                if ($workspace->users()->where('users.id', $validated['owner_id'])->exists()) {
                    $workspace->users()->updateExistingPivot($validated['owner_id'], ['role' => 'admin']);
                } else {
                    $workspace->users()->attach($validated['owner_id'], ['role' => 'admin']);
                }
            }
        }

        return redirect()->route('admin.index')->with('success', 'Business details updated.');
    }

    /**
     * Toggle super admin status for a user.
     */
    public function toggleSuperAdmin($id)
    {
        $user = User::findOrFail($id);
        
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot revoke your own admin rights.');
        }

        $user->update(['is_super_admin' => !$user->is_super_admin]);

        return redirect()->route('admin.index')->with('success', 'User permissions updated.');
    }

    /**
     * Toggle the active status of a workspace.
     */
    public function toggleWorkspaceStatus($id)
    {
        $workspace = Workspace::findOrFail($id);
        $workspace->update(['is_active' => !$workspace->is_active]);

        $status = $workspace->is_active ? 'activated' : 'suspended';
        return redirect()->route('admin.index')->with('success', "Business workspace has been {$status}.");
    }

    /**
     * Store a new user (personnel) manualy.
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'is_super_admin' => 'boolean',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_super_admin' => $validated['is_super_admin'] ?? false,
        ]);

        return redirect()->route('admin.index')->with('success', 'New personnel created successfully.');
    }

    /**
     * Update user details.
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($validated['password']) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('admin.index')->with('success', 'Personnel details updated.');
    }

    /**
     * Record a monthly payment and extend the license.
     */
    public function recordPayment($id)
    {
        $workspace = Workspace::findOrFail($id);
        
        // Extend trial/license ends at by 30 days
        $currentEnd = $workspace->trial_ends_at ? \Carbon\Carbon::parse($workspace->trial_ends_at) : \Carbon\Carbon::now();
        
        // If it's already expired, start from today. If it's still active, add to the end.
        $newEnd = $currentEnd->isPast() ? \Carbon\Carbon::now()->addDays(30) : $currentEnd->addDays(30);
        
        $workspace->update([
            'trial_ends_at' => $newEnd,
            'last_billed_at' => \Carbon\Carbon::now(),
        ]);

        // Create the History record
        \App\Models\SubscriptionPayment::create([
            'workspace_id' => $workspace->id,
            'amount' => \App\Services\TierService::getPrice($workspace->tier),
            'billed_at' => \Carbon\Carbon::now(),
            'extended_until' => $newEnd,
            'payment_method' => 'manual',
            'notes' => 'Monthly subscription extension recorded by Super Admin'
        ]);

        return redirect()->route('admin.index')->with('success', "Renewal Successful: {$workspace->company_name} is now active until " . $newEnd->format('d M Y'));
    }
}
