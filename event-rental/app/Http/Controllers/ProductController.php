<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
            'category',
            'invoiceItems' => function ($q) {
                $q->whereDate('created_at', now())->with('invoice');
            }
        ]);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        if ($request->category_id) {
            $query->where('product_category_id', $request->category_id);
        }

        // Sorting
        $sortField = $request->get('sort', 'name');
        $sortDirection = $request->get('direction', 'asc');
        $allowedSorts = ['name', 'sku', 'current_stock', 'unit_price_gross', 'created_at'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->latest();
        }

        return Inertia::render('Products/Index', [
            'products' => $query->paginate(24)->withQueryString(),
            'categories' => ProductCategory::all(),
            'filters' => $request->only(['search', 'category_id', 'sort', 'direction'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Products/Create', [
            'categories' => ProductCategory::all()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'sku' => 'nullable|string|unique:products,sku',
            'product_type' => 'required|in:physical,service',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'unit_price_gross' => 'required|numeric|min:0',
            'vat_rate' => 'required|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'acquisition_date' => 'nullable|date',
        ]);

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product added successfully!');
    }

    public function show($id)
    {
        $product = Product::with([
            'category',
            'stockMovements' => function ($q) {
                $q->orderBy('created_at', 'desc')->limit(20);
            }
        ])->findOrFail($id);

        return Inertia::render('Products/Show', [
            'product' => $product
        ]);
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return Inertia::render('Products/Edit', [
            'product' => $product,
            'categories' => ProductCategory::all()
        ]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'sku' => "nullable|string|unique:products,sku,{$id}",
            'product_type' => 'required|in:physical,service',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'unit_price_gross' => 'required|numeric|min:0',
            'vat_rate' => 'required|numeric|min:0',
            'current_stock' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'acquisition_date' => 'nullable|date',
        ]);

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product updated successfully!');
    }


    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product removed from inventory.');
    }

    public function adjustStock(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'quantity' => 'required|numeric|min:0.01',
            'direction' => 'required|in:in,out',
            'movement_type' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $quantity = $validated['direction'] === 'in' ? $validated['quantity'] : -$validated['quantity'];

        StockMovement::create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'direction' => $validated['direction'],
            'movement_type' => $validated['movement_type'],
            'notes' => $validated['notes']
        ]);

        $product->increment('current_stock', $quantity);

        return redirect()->back()->with('success', 'Stock adjustment recorded successfully.');
    }

    public function updatePartial(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'sku' => "nullable|string|unique:products,sku,{$id}",
            'unit_price_gross' => 'nullable|numeric|min:0',
            'name' => 'nullable|string'
        ]);

        $product->update($validated);

        return redirect()->back()->with('success', 'Asset attributes updated.');
    }
}
