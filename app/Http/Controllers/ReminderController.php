<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\Reminder;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    use ResolvesWorkspace;

    private function workspaceId(): int
    {
        return $this->currentWorkspaceId();
    }

    public function index()
    {
        $workspaceId = $this->workspaceId();
        $reminders = Reminder::whereHas('contact', function ($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        })->with('contact')->orderBy('remind_at')->get();

        return \Inertia\Inertia::render('Reminders/Index', [
            'reminders' => $reminders
        ]);
    }

    public function store(Request $request)
    {
        $workspaceId = $this->workspaceId();

        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'title'      => 'required|string|max:255',
            'remind_at'  => 'required|date|after:now',
        ]);

        // Verify the contact belongs to this workspace (prevents cross-workspace reminder injection)
        \App\Models\Contact::where('workspace_id', $workspaceId)
            ->findOrFail($validated['contact_id']);

        Reminder::create($validated);

        return back()->with('success', 'Follow-up scheduled.');
    }

    public function destroy($id)
    {
        $workspaceId = $this->workspaceId();

        // Scope the lookup to user's workspace via contact relationship (prevents cross-workspace deletion)
        $reminder = Reminder::whereHas('contact', function ($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        })->findOrFail($id);

        $reminder->delete();

        return back()->with('success', 'Reminder removed.');
    }
}
