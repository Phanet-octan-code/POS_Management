<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    /**
     * Audit Activity Logs Hub: Searchable, Filterable by User, Action, Module, Date.
     */
    public function index(Request $request): View
    {
        $search = $request->get('q');
        $module = $request->get('module', 'all');
        $action = $request->get('action', 'all');
        $userId = $request->get('user_id', 'all');
        $datePreset = $request->get('date_preset');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = ActivityLog::with('user.roles');

        // Apply Search Scope
        if (!empty($search)) {
            $query->search($search);
        }

        // Apply Module Filter
        if (!empty($module) && $module !== 'all') {
            $query->filterModule($module);
        }

        // Apply Action Filter
        if (!empty($action) && $action !== 'all') {
            $query->filterAction($action);
        }

        // Apply User Filter
        if (!empty($userId) && $userId !== 'all') {
            $query->filterUser($userId);
        }

        // Apply Date Range Filter
        if (!empty($datePreset) || (!empty($startDate) || !empty($endDate))) {
            $query->filterDateRange($datePreset, $startDate, $endDate);
        }

        $logs = $query->latest()->paginate(25)->withQueryString();

        // Available Modules for Dropdown
        $modules = [
            'products' => 'Products',
            'categories' => 'Categories',
            'brands' => 'Brands',
            'inventory' => 'Inventory',
            'customers' => 'Customers',
            'suppliers' => 'Suppliers',
            'purchases' => 'Purchases',
            'sales' => 'Sales & POS',
            'expenses' => 'Expenses',
            'users' => 'Users & Staff',
            'roles' => 'Roles & Permissions',
            'settings' => 'Settings',
        ];

        // Available Actions for Dropdown
        $actions = [
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
            'complete' => 'Completed',
            'adjust' => 'Adjusted',
        ];

        // All Users for User Filter Dropdown
        $users = User::orderBy('name')->get();

        // Summary counts for metric banner
        $totalLogsCount = ActivityLog::count();
        $todayLogsCount = ActivityLog::whereDate('created_at', today())->count();

        return view('activity-logs.index', compact(
            'logs',
            'modules',
            'actions',
            'users',
            'search',
            'module',
            'action',
            'userId',
            'datePreset',
            'startDate',
            'endDate',
            'totalLogsCount',
            'todayLogsCount'
        ));
    }
}
