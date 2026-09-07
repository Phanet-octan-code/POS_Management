<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use App\Services\ActivityLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Category::withCount('products')->with('parent');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->get('status') !== '' && $request->get('status') !== null) {
            $query->where('is_active', (bool) $request->get('status'));
        }

        $categories = $query->latest()->paginate(12)->withQueryString();
        $parentCategories = Category::whereNull('parent_id')->orderBy('name')->get();

        if ($request->ajax() && $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        }

        return view('categories.index', compact('categories', 'parentCategories'));
    }

    public function store(CategoryRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $validated['slug'] = !empty($validated['slug']) ? $validated['slug'] : Str::slug($validated['name']);
        
        // Ensure unique slug
        $originalSlug = $validated['slug'];
        $count = 1;
        while (Category::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = "{$originalSlug}-" . $count++;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $category = Category::create($validated);
        ActivityLoggerService::log('category.created', "Created category {$category->name}", $category);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$category->name}' created successfully.",
                'category' => $category,
            ]);
        }

        return back()->with('success', "Category '{$category->name}' created successfully.");
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $validated['slug'] = !empty($validated['slug']) ? $validated['slug'] : Str::slug($validated['name']);

        // Prevent setting category as its own parent
        if (isset($validated['parent_id']) && $validated['parent_id'] == $category->id) {
            $validated['parent_id'] = null;
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $category->update($validated);
        ActivityLoggerService::log('category.updated', "Updated category {$category->name}", $category);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$category->name}' updated successfully.",
                'category' => $category,
            ]);
        }

        return back()->with('success', "Category '{$category->name}' updated successfully.");
    }

    public function toggleStatus(Category $category): JsonResponse|RedirectResponse
    {
        $category->is_active = !$category->is_active;
        $category->save();

        $statusStr = $category->is_active ? 'activated' : 'deactivated';
        ActivityLoggerService::log('category.status_updated', "Category '{$category->name}' was {$statusStr}", $category);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => $category->is_active,
                'message' => "Category '{$category->name}' {$statusStr}.",
            ]);
        }

        return back()->with('success', "Category '{$category->name}' {$statusStr}.");
    }

    public function destroy(Category $category): RedirectResponse|JsonResponse
    {
        // Check if category has associated products
        $productCount = $category->products()->count();
        if ($productCount > 0) {
            $message = "Cannot delete category '{$category->name}' because it contains {$productCount} product(s). Please reassign or delete them first.";
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        $name = $category->name;
        $category->delete();
        ActivityLoggerService::log('category.deleted', "Deleted category {$name}");

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$name}' deleted successfully.",
            ]);
        }

        return back()->with('success', "Category '{$name}' deleted successfully.");
    }
}
