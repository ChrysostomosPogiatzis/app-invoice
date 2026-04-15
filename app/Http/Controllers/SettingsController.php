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
            'si_employee_rate' => 'nullable|numeric|min:0|max:100',
            'si_employer_rate' => 'nullable|numeric|min:0|max:100',
            'gesi_employee_rate' => 'nullable|numeric|min:0|max:100',
            'gesi_employer_rate' => 'nullable|numeric|min:0|max:100',
            'provident_employee_rate' => 'nullable|numeric|min:0|max:100',
            'provident_employer_rate' => 'nullable|numeric|min:0|max:100',
            'redundancy_rate' => 'nullable|numeric|min:0|max:100',
            'training_rate' => 'nullable|numeric|min:0|max:100',
            'cohesion_rate' => 'nullable|numeric|min:0|max:100',
            'holiday_rate' => 'nullable|numeric|min:0|max:100',
            'annual_tax_threshold' => 'nullable|numeric|min:0',
            'tax_brackets' => 'nullable|array',
            'tax_brackets.*.threshold' => 'required_with:tax_brackets|numeric|min:0',
            'tax_brackets.*.rate' => 'required_with:tax_brackets|numeric|min:0|max:100',
            'features' => 'nullable|array',
        ]);

        if (! empty($validated['tax_brackets'])) {
            $normalizedBrackets = collect($validated['tax_brackets'])
                ->map(fn (array $bracket) => [
                    'threshold' => (float) $bracket['threshold'],
                    'rate' => (float) $bracket['rate'],
                ])
                ->sortBy('threshold')
                ->values();

            $hasDuplicateThresholds = $normalizedBrackets
                ->pluck('threshold')
                ->duplicates()
                ->isNotEmpty();

            if ($hasDuplicateThresholds) {
                return back()->withErrors([
                    'tax_brackets' => 'Tax bracket thresholds must be unique.',
                ])->withInput();
            }

            $validated['tax_brackets'] = $normalizedBrackets->all();
        }

        $data = collect($validated)
            ->except(['features', 'logo'])
            ->toArray();

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
        $workspace = $this->currentWorkspace();
        if (!$workspace) {
            return back()->with('error', 'No active workspace found.');
        }

        // Tier Enforcement
        if (!\App\Services\TierService::canGenerateToken($workspace)) {
            $limits = \App\Services\TierService::getLimits($workspace->tier);
            return back()->with('error', "API token limit reached or feature not available for your {$workspace->tier} plan (Max: {$limits['max_tokens']}). Please upgrade for more integration access.");
        }

        $request->validate([
            'token_name' => 'required|string|max:255',
        ]);

        $tokenName = $request->input('token_name');
        $token = $request->user()->createToken($tokenName);

        return back()->with('success', 'Token generated successfully! Write down your secret key: ' . $token->plainTextToken);
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
