<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Provision Default Workspace (Node)
        $workspace = \App\Models\Workspace::create([
            'company_name' => $user->name . "'s Workspace",
            'email' => $user->email,
            'currency' => 'EUR',
            'tier' => 'starter',
            'invoice_prefix' => 'INV-',
            'quote_prefix' => 'QT-',
            'next_invoice_number' => 1001,
            'next_quote_number' => 1001,
            'trial_ends_at' => now()->addDays(7),
        ]);

        // Attach user to the new node as owner
        $user->workspaces()->attach($workspace->id, ['role' => 'admin']);
        $user->update(['current_workspace_id' => $workspace->id]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
