<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\ActivityLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Display listing of roles and available permissions.
     */
    public function index(): View
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('id')->get();
        $permissions = Permission::all()->groupBy('module');

        return view('roles.index', compact('roles', 'permissions'));
    }

    /**
     * Store a new custom role.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        ActivityLoggerService::log('role.created', "Created role {$role->name}", $role);

        return back()->with('success', "Role '{$role->name}' created successfully.");
    }

    /**
     * Show role details and assigned permission IDs (JSON for modal).
     */
    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        return response()->json([
            'success' => true,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'permission_ids' => $role->permissions->pluck('id')->all(),
                'permission_slugs' => $role->permissions->pluck('slug')->all(),
            ],
        ]);
    }

    /**
     * Update role details and assign permissions.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        // Keep original slug for core roles
        $isCore = in_array($role->slug, ['admin', 'super-admin', 'manager', 'cashier', 'staff']);
        $newSlug = $isCore ? $role->slug : Str::slug($validated['name']);

        $role->update([
            'name' => $validated['name'],
            'slug' => $newSlug,
            'description' => $validated['description'] ?? null,
        ]);

        // Sync permissions
        $role->permissions()->sync($validated['permissions'] ?? []);

        ActivityLoggerService::log('role.updated', "Updated permissions for role {$role->name}", $role);

        return back()->with('success', "Role '{$role->name}' and assigned permissions updated successfully.");
    }

    /**
     * Delete custom role (protect core roles).
     */
    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->slug, ['super-admin', 'admin', 'manager', 'cashier', 'staff'])) {
            return back()->withErrors(['error' => "Core system role '{$role->name}' cannot be deleted."]);
        }

        if ($role->users()->count() > 0) {
            return back()->withErrors(['error' => "Cannot delete role '{$role->name}' because it is currently assigned to {$role->users()->count()} user(s)."]);
        }

        $roleName = $role->name;
        $role->delete();

        ActivityLoggerService::log('role.deleted', "Deleted role {$roleName}");

        return back()->with('success', "Role '{$roleName}' deleted successfully.");
    }
}
