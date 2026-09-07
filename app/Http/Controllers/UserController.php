<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLoggerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of users with search, role filter, and status filter.
     */
    public function index(Request $request): View
    {
        $search = $request->input('q');
        $selectedRole = $request->input('role_id');
        $selectedStatus = $request->input('status');

        $users = User::with('roles')
            ->search($search)
            ->roleFilter($selectedRole)
            ->statusFilter($selectedStatus)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('users.index', compact('users', 'roles', 'search', 'selectedRole', 'selectedStatus'));
    }

    /**
     * Store a newly created user.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active', true);

        $user = User::create($data);

        if (!empty($data['roles'])) {
            $user->roles()->sync($data['roles']);
        }

        ActivityLoggerService::log('user.created', "Created user account {$user->email}", $user);

        return back()->with('success', "User '{$user->name}' created successfully.");
    }

    /**
     * Display detailed user information (supports JSON for modal preview).
     */
    public function show(User $user): JsonResponse
    {
        $user->load('roles');

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '—',
                'is_active' => (bool) $user->is_active,
                'status_label' => $user->is_active ? 'Active' : 'Deactivated',
                'roles' => $user->roles->map(fn($r) => ['id' => $r->id, 'name' => $r->name, 'slug' => $r->slug]),
                'sales_count' => $user->sales()->count(),
                'purchases_count' => $user->purchases()->count(),
                'expenses_count' => $user->expenses()->count(),
                'activity_count' => $user->activityLogs()->count(),
                'created_at' => $user->created_at?->format('M d, Y H:i A') ?? '—',
                'updated_at' => $user->updated_at?->format('M d, Y H:i A') ?? '—',
            ],
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Prevent primary admin from being disabled
        if ($user->id === 1) {
            $data['is_active'] = true;
        } else {
            $data['is_active'] = $request->boolean('is_active', true);
        }

        $user->update($data);

        if (isset($data['roles'])) {
            // Guarantee primary admin retains the admin role
            if ($user->id === 1) {
                $adminRoleId = Role::where('slug', 'admin')->value('id');
                if ($adminRoleId && !in_array($adminRoleId, $data['roles'])) {
                    $data['roles'][] = $adminRoleId;
                }
            }
            $user->roles()->sync($data['roles']);
        }

        ActivityLoggerService::log('user.updated', "Updated user account {$user->email}", $user);

        return back()->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Remove the specified user (soft-delete).
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === 1) {
            return back()->withErrors(['error' => 'The primary administrator account cannot be deleted.']);
        }

        if (Auth::id() === $user->id) {
            return back()->withErrors(['error' => 'You cannot delete your own authenticated account.']);
        }

        $userName = $user->name;
        $userEmail = $user->email;
        $user->delete();

        ActivityLoggerService::log('user.deleted', "Deleted user account {$userEmail}");

        return back()->with('success', "User '{$userName}' has been deleted.");
    }

    /**
     * Activate or Deactivate a user account.
     */
    public function toggleStatus(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if ($user->id === 1) {
            $msg = 'The primary administrator account cannot be deactivated.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['error' => $msg]);
        }

        if (Auth::id() === $user->id) {
            $msg = 'You cannot deactivate your own active session.';
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $msg], 422)
                : back()->withErrors(['error' => $msg]);
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'activated' : 'deactivated';
        $logAction = $user->is_active ? 'user.activated' : 'user.deactivated';
        ActivityLoggerService::log($logAction, "User account {$user->email} was {$statusText}", $user);

        $msg = "User '{$user->name}' has been {$statusText}.";

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'is_active' => (bool) $user->is_active,
                'status_label' => $user->is_active ? 'Active' : 'Deactivated',
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Reset a user's password directly by Admin.
     */
    public function resetPassword(Request $request, User $user): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        ActivityLoggerService::log('user.password_reset', "Admin reset password for user {$user->email}", $user);

        $msg = "Password for user '{$user->name}' has been reset successfully.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }
}
