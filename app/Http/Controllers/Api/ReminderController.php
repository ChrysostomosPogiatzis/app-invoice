<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Reminder;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    private function getWorkspaceId()
    {
        return auth()->user()->currentWorkspaceRecord()->id;
    }

    /**
     * List all reminders for current workspace.
     */
    public function index(Request $request)
    {
        $workspaceId = $this->getWorkspaceId();

        $query = Reminder::whereHas('contact', function ($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        })->with('contact');

        // Optional filter: only upcoming reminders
        if ($request->get('upcoming') === 'true') {
            $query->where('remind_at', '>=', now());
        }

        // Optional filter: by contact
        if ($request->has('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }

        return $query->orderBy('remind_at', 'asc')->paginate(30);
    }

    /**
     * Create a new reminder for a contact.
     */
    public function store(Request $request)
    {
        $workspaceId = $this->getWorkspaceId();

        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'title'      => 'required|string|max:255',
            'remind_at'  => 'required|date',
        ]);

        // Ensure contact belongs to this workspace
        $contact = Contact::where('workspace_id', $workspaceId)->findOrFail($validated['contact_id']);

        $reminder = $contact->reminders()->create([
            'title'     => $validated['title'],
            'remind_at' => $validated['remind_at'],
        ]);

        return response()->json($reminder->load('contact'), 201);
    }

    /**
     * Update an existing reminder.
     */
    public function update(Request $request, $id)
    {
        $workspaceId = $this->getWorkspaceId();

        $reminder = Reminder::whereHas('contact', function ($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        })->findOrFail($id);

        $validated = $request->validate([
            'title'     => 'sometimes|required|string|max:255',
            'remind_at' => 'sometimes|required|date',
        ]);

        $reminder->update($validated);

        return response()->json($reminder->load('contact'));
    }

    /**
     * Delete a reminder.
     */
    public function destroy($id)
    {
        $workspaceId = $this->getWorkspaceId();

        $reminder = Reminder::whereHas('contact', function ($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        })->findOrFail($id);

        $reminder->delete();

        return response()->json(null, 204);
    }
}
