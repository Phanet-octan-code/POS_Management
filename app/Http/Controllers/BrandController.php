<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandRequest;
use App\Models\Brand;
use App\Services\ActivityLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = Brand::withCount('products');

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

        $brands = $query->latest()->paginate(12)->withQueryString();

        if ($request->ajax() && $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $brands,
            ]);
        }

        return view('brands.index', compact('brands'));
    }

    public function store(BrandRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $validated['slug'] = !empty($validated['slug']) ? $validated['slug'] : Str::slug($validated['name']);

        // Ensure unique slug
        $originalSlug = $validated['slug'];
        $count = 1;
        while (Brand::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = "{$originalSlug}-" . $count++;
        }

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $brand = Brand::create($validated);
        ActivityLoggerService::log('brand.created', "Created brand {$brand->name}", $brand);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Brand '{$brand->name}' created successfully.",
                'brand' => $brand,
            ]);
        }

        return back()->with('success', "Brand '{$brand->name}' created successfully.");
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $validated['slug'] = !empty($validated['slug']) ? $validated['slug'] : Str::slug($validated['name']);

        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
                Storage::disk('public')->delete($brand->logo);
            }
            $validated['logo'] = $request->file('logo')->store('brands', 'public');
        }

        $validated['is_active'] = $request->boolean('is_active', true);

        $brand->update($validated);
        ActivityLoggerService::log('brand.updated', "Updated brand {$brand->name}", $brand);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Brand '{$brand->name}' updated successfully.",
                'brand' => $brand,
            ]);
        }

        return back()->with('success', "Brand '{$brand->name}' updated successfully.");
    }

    public function toggleStatus(Brand $brand): JsonResponse|RedirectResponse
    {
        $brand->is_active = !$brand->is_active;
        $brand->save();

        $statusStr = $brand->is_active ? 'activated' : 'deactivated';
        ActivityLoggerService::log('brand.status_updated', "Brand '{$brand->name}' was {$statusStr}", $brand);

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'is_active' => $brand->is_active,
                'message' => "Brand '{$brand->name}' {$statusStr}.",
            ]);
        }

        return back()->with('success', "Brand '{$brand->name}' {$statusStr}.");
    }

    public function destroy(Brand $brand): RedirectResponse|JsonResponse
    {
        // Check if brand has associated products
        $productCount = $brand->products()->count();
        if ($productCount > 0) {
            $message = "Cannot delete brand '{$brand->name}' because it is assigned to {$productCount} product(s). Please reassign them first.";
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        $name = $brand->name;
        if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();
        ActivityLoggerService::log('brand.deleted', "Deleted brand {$name}");

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Brand '{$name}' deleted successfully.",
            ]);
        }

        return back()->with('success', "Brand '{$name}' deleted successfully.");
    }
}
