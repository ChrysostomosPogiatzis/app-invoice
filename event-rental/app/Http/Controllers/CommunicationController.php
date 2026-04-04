<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\CallLog;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationController extends Controller
{
    use ResolvesWorkspace;

    /**
     * Display communication logs for the current workspace.
     */
    public function index(Request $request): Response
    {
        $workspaceId = $this->currentWorkspaceId();

        $query = CallLog::query()
            ->whereHas('contact', fn ($contactQuery) => $contactQuery->where('workspace_id', $workspaceId))
            ->with(['contact', 'contact.invoices']);

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($logQuery) use ($search) {
                $logQuery
                    ->where('call_notes', 'like', "%{$search}%")
                    ->orWhere('call_type', 'like', "%{$search}%")
                    ->orWhereHas('contact', function ($contactQuery) use ($search) {
                        $contactQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->call_type) {
            $query->where('call_type', $request->call_type);
        }

        return Inertia::render('Communications/Index', [
            'logs' => $query->orderByDesc('call_date')->paginate(25)->withQueryString(),
            'filters' => $request->only(['search', 'call_type']),
        ]);
    }

    /**
     * Log a new communication interaction.
     */
    public function store(Request $request)
    {
        $workspaceId = $this->currentWorkspaceId();

        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'call_type' => 'required|in:inbound,outbound,missed',
            'call_duration_seconds' => 'nullable|integer|min:0',
            'call_notes' => 'required|string',

            'invoice_id' => 'nullable|exists:invoices,id',
        ]);

        Contact::where('workspace_id', $workspaceId)->findOrFail($validated['contact_id']);

        CallLog::create([
            'contact_id' => $validated['contact_id'],

            'invoice_id' => $validated['invoice_id'] ?? null,
            'call_type' => $validated['call_type'],
            'call_duration_seconds' => $validated['call_duration_seconds'] ?? 0,
            'call_notes' => $validated['call_notes'],
            'call_date' => now(),
        ]);

        return back()->with('success', 'Communication log synchronized.');
    }

    /**
     * Update an existing communication log.
     */
    public function update(Request $request, $id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $log = CallLog::whereHas('contact', fn ($contactQuery) => $contactQuery->where('workspace_id', $workspaceId))
            ->findOrFail($id);

        $validated = $request->validate([
            'call_type' => 'required|in:inbound,outbound,missed',
            'call_duration_seconds' => 'nullable|integer|min:0',
            'call_notes' => 'required|string',
            'invoice_id' => 'nullable|exists:invoices,id',
        ]);

        $log->update([
            'invoice_id' => $validated['invoice_id'] ?? null,
            'call_type' => $validated['call_type'],
            'call_duration_seconds' => $validated['call_duration_seconds'] ?? 0,
            'call_notes' => $validated['call_notes'],
        ]);

        return back()->with('success', 'Communication log updated.');
    }

    /**
     * Delete an interaction log.
     */
    public function destroy($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $log = CallLog::whereHas('contact', fn ($contactQuery) => $contactQuery->where('workspace_id', $workspaceId))
            ->findOrFail($id);
        $log->delete();
        return back()->with('success', 'Log purged.');
    }
}
