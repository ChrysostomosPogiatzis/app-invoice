<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    use ResolvesWorkspace;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workspaceId = $this->currentWorkspaceId();

        $category = ProductCategory::create([
            'workspace_id' => $workspaceId,
            'name' => $validated['name']
        ]);

        return response()->json($category);
    }
}
