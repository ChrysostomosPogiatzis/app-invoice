<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStoredFile;
use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\Expense;
use App\Models\Workspace;
use App\Models\StaffMember;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    use ResolvesWorkspace, ResolvesStoredFile;

    /**
     * Display a listing of expenses.
     */
    public function index(Request $request)
    {
        $workspaceId = $this->currentWorkspaceId();
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

        $expenses = $query->paginate(20)->withQueryString()
            ->through(function (Expense $expense) {
                $expense->receipt_download_url = $expense->receipt_url
                    ? route('expenses.receipt.download', $expense->id)
                    : null;

                return $expense;
            });

        return Inertia::render('Finance/Expenses', [
            'expenses' => $expenses,
            'filters' => $request->only(['search', 'category', 'sort', 'direction']),
            'stats' => [
                'totalThisMonth' => Expense::where('workspace_id', $workspaceId)
                    ->whereMonth('expense_date', now()->month)
                    ->whereYear('expense_date', now()->year)
                    ->selectRaw('SUM(amount + IFNULL(si_employer, 0) + IFNULL(gesi_employer, 0) + IFNULL(provident_employer, 0) + IFNULL(redundancy_amount, 0) + IFNULL(training_amount, 0) + IFNULL(cohesion_amount, 0) + IFNULL(holiday_amount, 0) + IFNULL(vat_amount, 0)) as total')
                    ->value('total') ?? 0,
                'byCategory' => Expense::where('workspace_id', $workspaceId)
                    ->selectRaw('category, SUM(amount + IFNULL(si_employer, 0) + IFNULL(gesi_employer, 0) + IFNULL(provident_employer, 0) + IFNULL(redundancy_amount, 0) + IFNULL(training_amount, 0) + IFNULL(cohesion_amount, 0) + IFNULL(holiday_amount, 0) + IFNULL(vat_amount, 0)) as total')
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
        $workspaceId = $this->currentWorkspaceId();
        $workspace = Workspace::findOrFail($workspaceId);
        $staffMembers = StaffMember::where('workspace_id', $workspaceId)->get();

        return Inertia::render('Finance/ExpenseCreate', [
            'staff_members' => $staffMembers,
            'workspace' => $workspace
        ]);
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(Request $request)
    {
        $workspaceId = $this->currentWorkspaceId();

        $validated = $request->validate([
            'category' => 'required|string', // Changed to string to allow custom categories
            'amount' => 'required|numeric|min:0.01',
            'vat_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'required|date',
            'reminder_time' => 'nullable|string|max:5',
            'vendor_name' => 'nullable|string|max:255',
            'staff_member_id' => 'nullable|exists:staff_members,id',
            'is_payroll' => 'nullable|boolean',
            'gross_salary' => 'nullable|numeric',
            'si_employee' => 'nullable|numeric',
            'si_employer' => 'nullable|numeric',
            'gesi_employee' => 'nullable|numeric',
            'gesi_employer' => 'nullable|numeric',
            'tax_employee' => 'nullable|numeric',
            'provident_employee' => 'nullable|numeric',
            'provident_employer' => 'nullable|numeric',
            'redundancy_amount' => 'nullable|numeric',
            'training_amount' => 'nullable|numeric',
            'cohesion_amount' => 'nullable|numeric',
            'holiday_amount' => 'nullable|numeric',
            'union_amount' => 'nullable|numeric',
            'net_payable' => 'nullable|numeric',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|mimetypes:image/jpeg,image/png,application/pdf|max:5120',
            'notes' => 'nullable|string'
        ]);

        $receiptUrl = null;
        if ($request->hasFile('receipt_file')) {
            $receiptUrl = $request->file('receipt_file')->store('receipts', 'local');
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
        $workspaceId = $this->currentWorkspaceId();
        $expense = Expense::where('workspace_id', $workspaceId)->findOrFail($id);
        $expense->receipt_download_url = $expense->receipt_url
            ? route('expenses.receipt.download', $expense->id)
            : null;

        return Inertia::render('Finance/ExpenseShow', [
            'expense' => $expense
        ]);
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $expense = Expense::where('workspace_id', $workspaceId)->findOrFail($id);
        $workspace = Workspace::findOrFail($workspaceId);
        $staffMembers = StaffMember::where('workspace_id', $workspaceId)->get();

        return Inertia::render('Finance/ExpenseEdit', [
            'expense' => $expense,
            'workspace' => $workspace,
            'staff_members' => $staffMembers
        ]);
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(Request $request, $id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $expense = Expense::where('workspace_id', $workspaceId)->findOrFail($id);

        $validated = $request->validate([
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'vat_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'required|date',
            'reminder_time' => 'nullable|string|max:5',
            'vendor_name' => 'nullable|string|max:255',
            'staff_member_id' => 'nullable|exists:staff_members,id',
            'is_payroll' => 'nullable|boolean',
            'gross_salary' => 'nullable|numeric',
            'si_employee' => 'nullable|numeric',
            'si_employer' => 'nullable|numeric',
            'gesi_employee' => 'nullable|numeric',
            'gesi_employer' => 'nullable|numeric',
            'tax_employee' => 'nullable|numeric',
            'provident_employee' => 'nullable|numeric',
            'provident_employer' => 'nullable|numeric',
            'redundancy_amount' => 'nullable|numeric',
            'training_amount' => 'nullable|numeric',
            'cohesion_amount' => 'nullable|numeric',
            'holiday_amount' => 'nullable|numeric',
            'union_amount' => 'nullable|numeric',
            'net_payable' => 'nullable|numeric',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|mimetypes:image/jpeg,image/png,application/pdf|max:5120',
            'notes' => 'nullable|string'
        ]);

        if ($request->hasFile('receipt_file')) {
            $oldReceiptDisk = $this->storedFileDisk($expense->receipt_url);
            $oldReceiptPath = $this->normalizeStoredPath($expense->receipt_url);
            if ($oldReceiptDisk && $oldReceiptPath) {
                Storage::disk($oldReceiptDisk)->delete($oldReceiptPath);
            }

            $validated['receipt_url'] = $request->file('receipt_file')->store('receipts', 'local');
        }

        $expense->update($validated);

        return redirect()->route('expenses.index')->with('success', 'Expense updated successfully!');
    }

    /**
     * Remove the specified expense.
     */
    public function destroy($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $expense = Expense::where('workspace_id', $workspaceId)->findOrFail($id);

        $receiptDisk = $this->storedFileDisk($expense->receipt_url);
        $receiptPath = $this->normalizeStoredPath($expense->receipt_url);
        if ($receiptDisk && $receiptPath) {
            Storage::disk($receiptDisk)->delete($receiptPath);
        }

        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense record removed.');
    }

    public function downloadReceipt($id)
    {
        $expense = Expense::where('workspace_id', $this->currentWorkspaceId())->findOrFail($id);
        $fullPath = $this->storedFileAbsolutePath($expense->receipt_url);

        abort_unless($fullPath && file_exists($fullPath), 404, 'Receipt file not found.');

        return response()->file($fullPath);
    }
}
