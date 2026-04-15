<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\CallLog;
use App\Models\Reminder;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    private function getWorkspaceId()
    {
        return auth()->user()->currentWorkspaceRecord()->id;
    }

    public function index(Request $request)
    {
        $query = Contact::where('workspace_id', $this->getWorkspaceId());

        if ($request->has('phone')) {
            $phone = preg_replace('/[^0-9]/', '', $request->phone);
            $query->whereRaw("REPLACE(REPLACE(mobile_number, ' ', ''), '+', '') LIKE ?", ["%{$phone}%"]);
        }

        return $query->paginate(30);
    }

    public function lookup(Request $request)
    {
        $request->validate(['phone' => 'required|string']);
        
        $phone = preg_replace('/[^0-9]/', '', $request->phone); // Strip all non-numeric
        $suffix = (strlen($phone) >= 8) ? substr($phone, -8) : $phone; 

        $contact = Contact::where('workspace_id', $this->getWorkspaceId())
            ->whereRaw("REPLACE(REPLACE(mobile_number, ' ', ''), '+', '') LIKE ?", ["%{$suffix}"])
            ->with(['invoices', 'quotes', 'reminders', 'communications'])
            ->firstOrFail();

        return response()->json($contact);
    }

    public function lookupOrCreate(Request $request)
    {
        $request->validate(['phone' => 'required|string', 'name' => 'nullable|string']);
        
        $phone = preg_replace('/[^0-9]/', '', $request->phone);
        $suffix = (strlen($phone) >= 8) ? substr($phone, -8) : $phone; 
        $workspaceId = $this->getWorkspaceId();

        $contact = Contact::where('workspace_id', $workspaceId)
            ->whereRaw("REPLACE(REPLACE(mobile_number, ' ', ''), '+', '') LIKE ?", ["%{$suffix}"])
            ->first();

        if (!$contact) {
            $contact = Contact::create([
                'workspace_id' => $workspaceId,
                'name' => $request->name ?? "Unknown Caller ({$request->phone})",
                'mobile_number' => $request->phone,
                'contact_type' => 'customer'
            ]);
        }

        $contact->load(['invoices', 'quotes', 'reminders', 'communications']);

        return response()->json($contact);
    }

    public function history($id)
    {
        $contact = Contact::where('workspace_id', $this->getWorkspaceId())
            ->with(['invoices', 'quotes', 'communications'])
            ->findOrFail($id);

        return response()->json([
            'contact' => $contact,
            'statistics' => [
                'total_invoiced' => $contact->invoices->sum('grand_total_gross'),
                'outstanding_balance' => $contact->invoices->sum('balance_due'),
                'total_quotes' => $contact->quotes->count(),
            ]
        ]);
    }

    public function logCall(Request $request, $id)
    {
        $contact = Contact::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);
        
        $validated = $request->validate([
            'call_type' => 'required|string|in:incoming,outgoing,missed',
            'call_duration_seconds' => 'nullable|integer',
            'call_notes' => 'nullable|string',
            'call_date' => 'nullable|date',
        ]);

        $log = $contact->communications()->create(array_merge($validated, [
            'call_date' => $validated['call_date'] ?? now()
        ]));

        return response()->json($log, 201);
    }

    public function addReminder(Request $request, $id)
    {
        $contact = Contact::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'remind_at' => 'required|date',
        ]);

        $reminder = $contact->reminders()->create($validated);

        return response()->json($reminder, 201);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile_number' => 'nullable|string|max:25',
            'address' => 'nullable|string',
            'vat_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $contact = Contact::create(array_merge($validated, [
            'workspace_id' => $this->getWorkspaceId()
        ]));

        return response()->json($contact, 201);
    }

    public function show($id)
    {
        return Contact::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $contact = Contact::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile_number' => 'nullable|string|max:25',
            'address' => 'nullable|string',
            'vat_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $contact->update($validated);

        return response()->json($contact);
    }

    public function destroy($id)
    {
        $contact = Contact::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);
        $contact->delete();
        return response()->json(null, 204);
    }
}
