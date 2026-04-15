<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductCategoryController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspaceId = Auth::user()->workspaces()->first()->id;

        $category = ProductCategory::create([
            'workspace_id' => $workspaceId,
            'name' => $validated['name']
        ]);

        return response()->json($category);
    }
}
