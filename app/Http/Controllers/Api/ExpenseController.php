<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    private function getWorkspaceId()
    {
        return auth()->user()->currentWorkspaceRecord()->id;
    }

    public function index()
    {
        return Expense::where('workspace_id', $this->getWorkspaceId())
            ->orderBy('expense_date', 'desc')
            ->paginate(30);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'vat_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'required|date',
            'vendor_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $expense = Expense::create(array_merge($validated, [
            'workspace_id' => $this->getWorkspaceId()
        ]));

        return response()->json($expense, 201);
    }

    public function show($id)
    {
        return Expense::with('attachments')
            ->where('workspace_id', $this->getWorkspaceId())
            ->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);

        $validated = $request->validate([
            'category' => 'sometimes|required|string',
            'amount' => 'sometimes|required|numeric|min:0.01',
            'vat_amount' => 'nullable|numeric|min:0',
            'expense_date' => 'sometimes|required|date',
            'vendor_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);

        return response()->json($expense);
    }

    public function destroy($id)
    {
        $expense = Expense::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);
        $expense->delete();
        return response()->json(null, 204);
    }
}
