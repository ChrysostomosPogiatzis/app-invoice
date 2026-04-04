<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private function getWorkspaceId()
    {
        return auth()->user()->currentWorkspaceRecord()->id;
    }

    public function index()
    {
        return Product::where('workspace_id', $this->getWorkspaceId())->paginate(30);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'unit_price_net' => 'required|numeric|min:0',
            'vat_rate' => 'required|numeric|min:0',
            'current_stock' => 'nullable|numeric',
            'product_type' => 'nullable|string|in:physical,service,digital',
            'purchase_price' => 'nullable|numeric|min:0',
            'brand' => 'nullable|string',
            'model' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive,discontinued',
        ]);

        $product = Product::create(array_merge($validated, [
            'workspace_id' => $this->getWorkspaceId()
        ]));

        return response()->json($product, 201);
    }

    public function show($id)
    {
        return Product::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $product = Product::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'unit_price_net' => 'sometimes|required|numeric|min:0',
            'vat_rate' => 'sometimes|required|numeric|min:0',
            'current_stock' => 'nullable|numeric',
            'product_type' => 'nullable|string|in:physical,service,digital',
            'purchase_price' => 'nullable|numeric|min:0',
            'brand' => 'nullable|string',
            'model' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive,discontinued',
        ]);

        $product->update($validated);

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);
        $product->delete();
        return response()->json(null, 204);
    }
}
