<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class NotificationService
{
    /**
     * Create and dispatch a system notification.
     */
    public function createNotification(
        string $type,
        string $title,
        string $message,
        ?string $link = null,
        array $extraData = []
    ): Notification {
        $currency = Setting::get('currency_symbol', '$');

        $payload = array_merge([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link ?? route('notifications.index'),
            'currency' => $currency,
            'actor_id' => Auth::id(),
            'actor_name' => Auth::user()?->name ?? 'System',
        ], $extraData);

        return Notification::create([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'notifiable_type' => User::class,
            'notifiable_id' => Auth::id() ?? 1,
            'data' => json_encode($payload),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * 1. LOW STOCK NOTIFICATION
     */
    public function notifyLowStock(Product $product, ?int $currentStock = null): Notification
    {
        $stock = $currentStock ?? (int) $product->stock_quantity;

        return $this->createNotification(
            type: 'low_stock',
            title: 'Low Stock Warning',
            message: "Product '{$product->name}' (SKU: {$product->sku}) is running low! Only {$stock} units left (Alert at {$product->alert_quantity}).",
            link: route('inventory.index'),
            extraData: [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'stock' => $stock,
                'alert_quantity' => $product->alert_quantity,
            ]
        );
    }

    /**
     * 2. OUT OF STOCK NOTIFICATION
     */
    public function notifyOutOfStock(Product $product): Notification
    {
        return $this->createNotification(
            type: 'out_of_stock',
            title: 'Out of Stock Alert',
            message: "Product '{$product->name}' (SKU: {$product->sku}) is completely OUT OF STOCK (0 units left)!",
            link: route('inventory.index'),
            extraData: [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'stock' => 0,
            ]
        );
    }

    /**
     * 3. NEW SALE NOTIFICATION
     */
    public function notifyNewSale(Sale $sale): Notification
    {
        $currency = Setting::get('currency_symbol', '$');
        $cashier = $sale->user?->name ?? 'Cashier';
        $totalFormatted = number_format($sale->total_amount, 2);

        return $this->createNotification(
            type: 'new_sale',
            title: 'New Sale Completed',
            message: "New sale {$sale->invoice_no} completed by {$cashier} for {$currency}{$totalFormatted}.",
            link: route('sales.show', $sale->id),
            extraData: [
                'sale_id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'amount' => $sale->total_amount,
                'cashier' => $cashier,
            ]
        );
    }

    /**
     * 4. NEW PURCHASE NOTIFICATION
     */
    public function notifyNewPurchase(Purchase $purchase): Notification
    {
        $currency = Setting::get('currency_symbol', '$');
        $supplier = $purchase->supplier?->name ?? 'Supplier';
        $totalFormatted = number_format($purchase->total_amount, 2);
        $purchaseNo = $purchase->reference_no ?? $purchase->purchase_number ?? ('#' . $purchase->id);

        return $this->createNotification(
            type: 'new_purchase',
            title: 'New Purchase Received',
            message: "Purchase {$purchaseNo} from {$supplier} received for {$currency}{$totalFormatted}.",
            link: route('purchases.show', $purchase->id),
            extraData: [
                'purchase_id' => $purchase->id,
                'reference_no' => $purchaseNo,
                'amount' => $purchase->total_amount,
                'supplier' => $supplier,
            ]
        );
    }

    /**
     * 5. PAYMENT NOTIFICATION
     */
    public function notifyPayment(Payment $payment, string $reference = ''): Notification
    {
        $currency = Setting::get('currency_symbol', '$');
        $amountFormatted = number_format($payment->amount, 2);
        $method = ucfirst(str_replace('_', ' ', $payment->payment_method));

        $refText = $reference ? " for {$reference}" : '';

        return $this->createNotification(
            type: 'payment',
            title: 'Payment Received',
            message: "Payment of {$currency}{$amountFormatted} recorded via {$method}{$refText}.",
            link: route('sales.index'),
            extraData: [
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'reference' => $reference,
            ]
        );
    }

    /**
     * 6. EXPENSE NOTIFICATION
     */
    public function notifyExpense(Expense $expense): Notification
    {
        $currency = Setting::get('currency_symbol', '$');
        $amountFormatted = number_format($expense->amount, 2);
        $category = $expense->category?->name ?? 'General';

        return $this->createNotification(
            type: 'expense',
            title: 'Expense Recorded',
            message: "Expense of {$currency}{$amountFormatted} recorded for '{$expense->name}' under category {$category}.",
            link: route('expenses.index'),
            extraData: [
                'expense_id' => $expense->id,
                'name' => $expense->name,
                'amount' => $expense->amount,
                'category' => $category,
            ]
        );
    }

    /**
     * Check a product's current stock and trigger stock alerts if necessary.
     */
    public function checkProductStockAlert(Product $product, int $newStock): ?Notification
    {
        if ($newStock <= 0) {
            return $this->notifyOutOfStock($product);
        }

        if ($newStock <= $product->alert_quantity) {
            return $this->notifyLowStock($product, $newStock);
        }

        return null;
    }

    /**
     * Get total unread notifications count across all system alerts.
     */
    public function getUnreadCount(): int
    {
        return Notification::whereNull('read_at')->count();
    }

    /**
     * Get recent notifications (both unread and read).
     */
    public function getRecentNotifications(int $limit = 10): Collection
    {
        return Notification::orderByDesc('created_at')->limit($limit)->get();
    }

    /**
     * Get unread notifications for navbar dropdown.
     */
    public function getNavbarNotifications(int $limit = 6): Collection
    {
        return Notification::whereNull('read_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllAsRead(): int
    {
        return Notification::whereNull('read_at')->update(['read_at' => now()]);
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead(string $id): bool
    {
        return (bool) Notification::where('id', $id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
