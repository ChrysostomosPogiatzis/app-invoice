<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    private function getWorkspaceId()
    {
        return auth()->user()->currentWorkspaceRecord()->id;
    }

    /**
     * @OA\Get(path="/api/expenses", tags={"Expenses"}, summary="List expenses (paginated)",
     *     description="Get a filtered list of expenses.",
     *     security={{"BearerToken":{}}},
     *     @OA\Parameter(name="search", in="query", description="Search vendor or category", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="category", in="query", description="Filter by category", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="start_date", in="query", description="Start date (YYYY-MM-DD)", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end_date", in="query", description="End date (YYYY-MM-DD)", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="page", in="query", description="Page number", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Paginated expense list", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Expense")),
     *         @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function index(Request $request)
    {
        $query = Expense::where('workspace_id', $this->getWorkspaceId());

        // Search by vendor or category
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('vendor_name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('expense_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where('expense_date', '<=', $request->end_date);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(30);

        $expenses->getCollection()->transform(function ($expense) {
            $expense->receipt_download_url = $expense->receipt_url
                ? url("/api/expenses/{$expense->id}/receipt")
                : null;
            return $expense;
        });

        return $expenses;
    }

    /**
     * @OA\Post(path="/api/expenses", tags={"Expenses"}, summary="Create expense (with file upload)",
     *     description="Create an expense. To upload a receipt, use 'multipart/form-data'.",
     *     security={{"BearerToken":{}}},
     *     @OA\RequestBody(required=true,
     *         @OA\MediaType(mediaType="multipart/form-data",
     *             @OA\Schema(required={"category","amount","expense_date"},
     *                 @OA\Property(property="category", type="string", example="Office Supplies"),
     *                 @OA\Property(property="amount", type="number", format="float", example=50.00),
     *                 @OA\Property(property="vat_amount", type="number", format="float", example=9.50),
     *                 @OA\Property(property="expense_date", type="string", format="date", example="2026-04-16"),
     *                 @OA\Property(property="vendor_name", type="string", nullable=true, example="IKEA"),
     *                 @OA\Property(property="receipt_file", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Expense created", @OA\JsonContent(ref="#/components/schemas/Expense")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category'     => 'required|string',
            'amount'       => 'required|numeric|min:0.01',
            'vat_amount'   => 'nullable|numeric|min:0',
            'expense_date' => 'required|date',
            'vendor_name'  => 'nullable|string',
            'notes'        => 'nullable|string',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $receiptUrl = null;
        if ($request->hasFile('receipt_file')) {
            $receiptUrl = $request->file('receipt_file')->store('receipts', 'local');
        }

        $expense = Expense::create(array_merge($validated, [
            'workspace_id' => $this->getWorkspaceId(),
            'receipt_url'  => $receiptUrl
        ]));

        return response()->json($expense, 201);
    }

    public function show($id)
    {
        $expense = Expense::with('attachments')
            ->where('workspace_id', $this->getWorkspaceId())
            ->findOrFail($id);

        $expense->receipt_download_url = $expense->receipt_url
            ? url("/api/expenses/{$expense->id}/receipt")
            : null;

        return response()->json($expense);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);

        $validated = $request->validate([
            'category'     => 'sometimes|required|string',
            'amount'       => 'sometimes|required|numeric|min:0.01',
            'vat_amount'   => 'nullable|numeric|min:0',
            'expense_date' => 'sometimes|required|date',
            'vendor_name'  => 'nullable|string',
            'notes'        => 'nullable|string',
            'receipt_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        if ($request->hasFile('receipt_file')) {
            // Optional: delete old file
            if ($expense->receipt_url) {
                Storage::disk('local')->delete($expense->receipt_url);
            }
            $validated['receipt_url'] = $request->file('receipt_file')->store('receipts', 'local');
        }

        $expense->update($validated);

        return response()->json($expense);
    }

    public function destroy($id)
    {
        $expense = Expense::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);
        $expense->delete();
        return response()->json(null, 204);
    }

    public function downloadReceipt($id)
    {
        $expense = Expense::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);

        if (!$expense->receipt_url || !Storage::disk('local')->exists($expense->receipt_url)) {
            abort(404, 'Receipt not found');
        }

        $path = Storage::disk('local')->path($expense->receipt_url);
        return response()->file($path);
    }
}
