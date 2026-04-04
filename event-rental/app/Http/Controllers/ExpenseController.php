<?php

namespace App\Http\Controllers;

use App\Models\Expense;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(Request $request)
    {
        $workspaceId = Auth::user()->workspaces()->first()->id;
        $query = Expense::where('workspace_id', $workspaceId);

        if ($request->search) {
            $query->where('vendor_name', 'like', "%{$request->search}%");
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        // Sorting
        $sortField = $request->get('sort', 'expense_date');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['vendor_name', 'category', 'amount', 'expense_date', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->latest('expense_date');
        }

        $expenses = $query->paginate(20)->withQueryString();

        return Inertia::render('Finance/Expenses', [
            'expenses' => $expenses,
            'filters' => $request->only(['search', 'category', 'sort', 'direction']),
            'stats' => [
                'totalThisMonth' => Expense::where('workspace_id', $workspaceId)
                    ->whereMonth('expense_date', now()->month)
                    ->whereYear('expense_date', now()->year)
                    ->sum('amount'),
                'byCategory' => Expense::where('workspace_id', $workspaceId)
                    ->selectRaw('category, sum(amount) as total')
                    ->groupBy('category')
                    ->get()
            ]
        ]);
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create()
    {
        $workspaceId = Auth::user()->workspaces()->first()->id;

        return Inertia::render('Finance/ExpenseCreate', []);
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(Request $request)
    {
        $workspaceId = Auth::user()->workspaces()->first()->id;

        $validated = $request->validate([
            'category' => 'required|in:fuel,staff_wages,sub_rental,marketing,utility,other',
            'amount' => 'required|numeric|min:0.01',
            'vat_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'required|date',
            'reminder_time' => 'nullable|string|max:5',
            'vendor_name' => 'nullable|string|max:255',
            'receipt_file' => 'nullable|file|mimes:jpg,png,pdf|max:5120', // 5MB max
        ]);

        $receiptUrl = null;
        if ($request->hasFile('receipt_file')) {
            $path = $request->file('receipt_file')->store('receipts', 'public');
            $receiptUrl = \Illuminate\Support\Facades\Storage::url($path);
        }

        Expense::create(array_merge($validated, [
            'workspace_id' => $workspaceId,
            'receipt_url' => $receiptUrl
        ]));

        return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully!');
    }

    /**
     * Display the specified expense.
     */
    public function show($id)
    {
        $workspaceId = Auth::user()->workspaces()->first()->id;
        $expense = Expense::where('workspace_id', $workspaceId)->findOrFail($id);

        return Inertia::render('Finance/ExpenseShow', [
            'expense' => $expense
        ]);
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit($id)
    {
        $workspaceId = Auth::user()->workspaces()->first()->id;
        $expense = Expense::where('workspace_id', $workspaceId)->findOrFail($id);

        return Inertia::render('Finance/ExpenseEdit', [
            'expense' => $expense
        ]);
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(Request $request, $id)
    {
        $workspaceId = Auth::user()->workspaces()->first()->id;
        $expense = Expense::where('workspace_id', $workspaceId)->findOrFail($id);

        $validated = $request->validate([
            'category' => 'required|in:fuel,staff_wages,sub_rental,marketing,utility,other',
            'amount' => 'required|numeric|min:0.01',
            'vat_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'required|date',
            'reminder_time' => 'nullable|string|max:5',
            'vendor_name' => 'nullable|string|max:255',
            'receipt_file' => 'nullable|file|mimes:jpg,png,pdf|max:5120',
        ]);

        if ($request->hasFile('receipt_file')) {
            $path = $request->file('receipt_file')->store('receipts', 'public');
            $validated['receipt_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        $expense->update($validated);

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully!');
    }

    /**
     * Remove the specified expense.
     */
    public function destroy($id)
    {
        $workspaceId = Auth::user()->workspaces()->first()->id;
        $expense = Expense::where('workspace_id', $workspaceId)->findOrFail($id);
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense record removed.');
    }
}
