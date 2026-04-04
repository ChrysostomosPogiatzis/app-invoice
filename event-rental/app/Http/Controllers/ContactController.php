<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    use ResolvesWorkspace;

    public function index(Request $request)
    {
        $workspaceId = $this->currentWorkspaceId();
        $query = Contact::where('workspace_id', $workspaceId)->withCount(['invoices', 'reminders']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('company_name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->type) {
            $query->where('contact_type', $request->type);
        }

        // Sorting
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $allowedSorts = ['name', 'company_name', 'email', 'contact_type', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('name');
        }

        return Inertia::render('Contacts/Index', [
            'contacts' => $query->paginate(24)->withQueryString(),
            'filters' => $request->only(['search', 'type', 'sort', 'direction'])
        ]);
    }

    public function show($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $contact = Contact::where('workspace_id', $workspaceId)
            ->with([
                'invoices' => fn ($query) => $query->orderByDesc('date'),
                'communications' => fn ($query) => $query->orderByDesc('call_date'),
                'reminders' => fn ($query) => $query->orderBy('remind_at'),
            ])
            ->findOrFail($id);

        return Inertia::render('Contacts/Show', [
            'contact' => $contact,
        ]);
    }

    public function create()
    {
        return Inertia::render('Contacts/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile_number' => 'nullable|string|max:255',
            'vat_number' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'contact_type' => 'required|in:customer,lead,vendor,individual',
            'general_info' => 'nullable|string',
        ]);

        $workspaceId = $this->currentWorkspaceId();
        $contact = Contact::create(array_merge($validated, ['workspace_id' => $workspaceId]));

        return redirect()->route('contacts.index')->with('success', 'Contact created successfully.');
    }

    public function edit(Contact $contact)
    {
        return Inertia::render('Contacts/Edit', [
            'contact' => $contact
        ]);
    }

    public function update(Request $request, $id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $contact = Contact::where('workspace_id', $workspaceId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'mobile_number' => 'nullable|string|max:255',
            'vat_number' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'contact_type' => 'required|in:customer,lead,vendor,individual',
            'general_info' => 'nullable|string',
        ]);

        $contact->update($validated);

        return redirect()->route('contacts.index')->with('success', 'Contact profile synchronized.');
    }

    public function destroy(Contact $contact)
    {
        abort_unless($contact->workspace_id === $this->currentWorkspaceId(), 404);
        $contact->delete();
        return redirect()->route('contacts.index')->with('success', 'Contact removed from CRM.');
    }
}
