<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLoggerService
{
    /**
     * Record an audit activity log with human-readable description and module category.
     * Examples:
     * - "Admin created product 'iPhone 15'"
     * - "Cashier completed sale INV-2026-000001"
     * - "Manager updated customer 'John Doe'"
     * - "Admin deleted supplier 'ABC Logistics'"
     */
    public static function log(
        string $action,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        ?string $module = null
    ): ActivityLog {
        $user = Auth::user();

        // Determine user actor prefix (e.g. "Admin", "Cashier", "Manager", "Staff", or "System")
        $actorPrefix = 'System';
        if ($user) {
            $role = $user->roles()->first();
            $actorPrefix = $role ? ucfirst($role->name) : ($user->name ?: 'User');
        }

        // Format description to start with Actor Role if not already present
        $cleanDesc = trim($description ?? $action);
        if (!str_starts_with(strtolower($cleanDesc), strtolower($actorPrefix))) {
            $cleanDesc = "{$actorPrefix} {$cleanDesc}";
        }

        // Auto-detect module category if not provided
        if (empty($module)) {
            $module = static::detectModule($action, $subject);
        }

        return ActivityLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'description' => $cleanDesc,
            'ip_address' => Request::ip() ?? '127.0.0.1',
            'user_agent' => Request::userAgent(),
            'properties' => !empty($properties) ? $properties : null,
        ]);
    }

    /**
     * Auto-infer the module name from action slug or subject model.
     */
    protected static function detectModule(string $action, ?Model $subject = null): string
    {
        if ($subject) {
            $class = class_basename($subject);
            return match ($class) {
                'Product' => 'products',
                'Category' => 'categories',
                'Brand' => 'brands',
                'Customer' => 'customers',
                'Supplier' => 'suppliers',
                'Purchase', 'PurchaseItem' => 'purchases',
                'Sale', 'SaleItem' => 'sales',
                'Payment' => 'payments',
                'ReturnOrder', 'ReturnItem' => 'returns',
                'Expense', 'ExpenseCategory' => 'expenses',
                'User' => 'users',
                'Role' => 'roles',
                'Setting' => 'settings',
                'StockMovement', 'ProductStock' => 'inventory',
                default => strtolower($class)
            };
        }

        $prefix = explode('.', $action)[0] ?? explode('_', $action)[0];
        return match ($prefix) {
            'product' => 'products',
            'category' => 'categories',
            'brand' => 'brands',
            'customer' => 'customers',
            'supplier' => 'suppliers',
            'purchase' => 'purchases',
            'sale' => 'sales',
            'payment' => 'payments',
            'return' => 'returns',
            'expense' => 'expenses',
            'inventory', 'stock' => 'inventory',
            'user' => 'users',
            'role', 'permission' => 'roles',
            'settings', 'setting' => 'settings',
            default => $prefix ?: 'system'
        };
    }
}
