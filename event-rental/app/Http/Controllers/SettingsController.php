<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    use ResolvesWorkspace;

    /**
     * Display the workspace settings.
     */
    public function edit()
    {
        $workspace = $this->currentWorkspace();

        $workspace->load('features');

        return Inertia::render('Settings/Edit', [
            'workspace' => $workspace,
            'tokens' => auth()->user()->tokens ?? [],
            'flash' => session('flash')
        ]);
    }

    /**
     * Update the workspace settings.
     */
    public function update(Request $request)
    {
        $workspace = $this->currentWorkspace();

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'vat_number' => 'nullable|string|max:255',
            'tic_number' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:25',
            'email' => 'nullable|email|max:255',
            'currency' => 'required|string|size:3',
            'iban' => 'nullable|string|max:34',
            'bic' => 'nullable|string|max:11',
            'logo' => 'nullable|image|max:2048',
            'brand_color' => 'nullable|string|max:7',
            'invoice_prefix' => 'nullable|string|max:10',
            'next_invoice_number' => 'nullable|integer|min:1',
            'default_invoice_notes' => 'nullable|string',
            'features' => 'nullable|array',
        ]);

        $data = $request->except(['features', 'logo']);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo_url'] = '/storage/' . $path;
        }

        $workspace->update($data);

        if ($request->has('features')) {
            foreach ($request->input('features') as $featureName => $isEnabled) {
                $workspace->features()->updateOrCreate(
                    ['feature_name' => $featureName],
                    ['is_enabled' => $isEnabled]
                );
            }
        }

        return back()->with('success', 'Corporate profile updated successfully!');
    }

    /**
     * Generate an API token for the user.
     */
    public function generateToken(Request $request)
    {
        $request->validate([
            'token_name' => 'required|string|max:255',
        ]);

        $tokenName = $request->input('token_name');
        $token = $request->user()->createToken($tokenName);

        return back()->with('flash', [
            'type' => 'success',
            'message' => 'Token generated successfully!',
            'plain_text_token' => $token->plainTextToken
        ]);
    }

    /**
     * Revoke an API token.
     */
    public function revokeToken(Request $request, $tokenId)
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        return back()->with('success', 'API token revoked.');
    }
}
