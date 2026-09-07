<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Product;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Notification Center Hub: Filterable by Type, with KPI metrics.
     */
    public function index(Request $request): View
    {
        $type = $request->get('type', 'all');
        $status = $request->get('status', 'all');

        $query = Notification::query();

        if (!empty($type) && $type !== 'all') {
            if ($type === 'stock_alerts') {
                $query->whereIn('type', ['low_stock', 'out_of_stock']);
            } else {
                $query->where('type', $type);
            }
        }

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // Metrics for KPI summaries
        $totalUnread = $this->notificationService->getUnreadCount();
        $stockAlertsCount = Notification::whereIn('type', ['low_stock', 'out_of_stock'])->whereNull('read_at')->count();
        $salesCount = Notification::where('type', 'new_sale')->whereNull('read_at')->count();
        $purchasesCount = Notification::where('type', 'new_purchase')->whereNull('read_at')->count();
        $paymentsCount = Notification::where('type', 'payment')->whereNull('read_at')->count();
        $expensesCount = Notification::where('type', 'expense')->whereNull('read_at')->count();

        // Critical stock inventory list for reorder action
        $criticalProducts = Product::where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'alert_quantity')
            ->orderBy('stock_quantity')
            ->take(10)
            ->get();

        return view('notifications.index', compact(
            'notifications',
            'type',
            'status',
            'totalUnread',
            'stockAlertsCount',
            'salesCount',
            'purchasesCount',
            'paymentsCount',
            'expensesCount',
            'criticalProducts'
        ));
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(): RedirectResponse|JsonResponse
    {
        $count = $this->notificationService->markAllAsRead();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'All notifications marked as read.',
                'count' => $count
            ]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(string $id): RedirectResponse|JsonResponse
    {
        $success = $this->notificationService->markAsRead($id);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => $success,
                'message' => 'Notification marked as read.'
            ]);
        }

        return back()->with('success', 'Notification marked as read.');
    }
}
