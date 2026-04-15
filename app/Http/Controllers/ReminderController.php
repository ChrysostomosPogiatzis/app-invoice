<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index()
    {
        $workspaceId = auth()->user()->workspaces()->first()->id;
        $reminders = Reminder::whereHas('contact', function($q) use ($workspaceId) {
            $q->where('workspace_id', $workspaceId);
        })->with('contact')->orderBy('remind_at')->get();

        return \Inertia\Inertia::render('Reminders/Index', [
            'reminders' => $reminders
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'title' => 'required|string|max:255',
            'remind_at' => 'required|date|after:now',
        ]);

        Reminder::create($validated);

        return back()->with('success', 'Follow-up scheduled.');
    }

    public function destroy($id)
    {
        $reminder = Reminder::findOrFail($id);
        $reminder->delete();
        
        return back()->with('success', 'Reminder removed.');
    }
}
