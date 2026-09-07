<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ActivityLoggerService;
use App\Services\NotificationService;
use Tests\TestCase;

class NotificationAndActivityLogTest extends TestCase
{
    protected User $admin;
    protected User $cashier;
    protected User $manager;
    protected User $staff;
    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('email', 'admin@pos.com')->first();
        $this->cashier = User::where('email', 'cashier@pos.com')->first();
        $this->manager = User::where('email', 'manager@pos.com')->first();
        $this->staff = User::where('email', 'staff@pos.com')->first();

        $this->notificationService = app(NotificationService::class);
    }

    /**
     * 1. Test Low Stock Notification creation and properties.
     */
    public function test_low_stock_notification(): void
    {
        $product = Product::first() ?? Product::factory()->create(['stock_quantity' => 4, 'alert_quantity' => 10]);

        $notification = $this->notificationService->notifyLowStock($product, 4);

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        $this->assertEquals('low_stock', $notification->type);
        $this->assertTrue($notification->is_unread);
        $this->assertStringContainsString($product->name, $notification->message);
        $this->assertEquals('warning', $notification->color);
        $this->assertEquals('bi-exclamation-triangle-fill', $notification->icon);
    }

    /**
     * 2. Test Out of Stock Notification creation and properties.
     */
    public function test_out_of_stock_notification(): void
    {
        $product = Product::first() ?? Product::factory()->create();

        $notification = $this->notificationService->notifyOutOfStock($product);

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        $this->assertEquals('out_of_stock', $notification->type);
        $this->assertStringContainsString('OUT OF STOCK', $notification->message);
        $this->assertEquals('danger', $notification->color);
        $this->assertEquals('bi-x-circle-fill', $notification->icon);
    }

    /**
     * 3. Test New Sale Notification creation.
     */
    public function test_new_sale_notification(): void
    {
        $sale = Sale::first();
        if (!$sale) {
            $sale = Sale::create([
                'user_id' => $this->cashier->id,
                'invoice_no' => 'INV-2026-TEST01',
                'sale_date' => now(),
                'subtotal' => 100,
                'total_amount' => 100,
                'paid_amount' => 100,
                'payment_method' => 'cash',
                'payment_status' => 'paid',
            ]);
        }

        $notification = $this->notificationService->notifyNewSale($sale);

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        $this->assertEquals('new_sale', $notification->type);
        $this->assertStringContainsString($sale->invoice_no, $notification->message);
        $this->assertEquals('success', $notification->color);
        $this->assertEquals('bi-cart-check-fill', $notification->icon);
    }

    /**
     * 4. Test New Purchase Notification creation.
     */
    public function test_new_purchase_notification(): void
    {
        $supplier = Supplier::first() ?? Supplier::create(['name' => 'Global Imports', 'phone' => '123']);
        $poRef = 'PO-2026-' . uniqid();
        $purchase = Purchase::create([
            'reference_no' => $poRef,
            'supplier_id' => $supplier->id,
            'user_id' => $this->admin->id,
            'purchase_date' => now(),
            'total_amount' => 500,
            'paid_amount' => 500,
            'status' => 'received',
            'payment_status' => 'paid',
        ]);

        $notification = $this->notificationService->notifyNewPurchase($purchase);

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        $this->assertEquals('new_purchase', $notification->type);
        $this->assertStringContainsString($poRef, $notification->message);
        $this->assertEquals('primary', $notification->color);
    }

    /**
     * 5. Test Payment Notification creation.
     */
    public function test_payment_notification(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY-2026-' . uniqid(),
            'payable_type' => Sale::class,
            'payable_id' => 1,
            'amount' => 75.50,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
        ]);

        $notification = $this->notificationService->notifyPayment($payment, 'INV-2026-TEST01');

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        $this->assertEquals('payment', $notification->type);
        $this->assertStringContainsString('75.50', $notification->message);
        $this->assertEquals('info', $notification->color);
    }

    /**
     * 6. Test Expense Notification creation.
     */
    public function test_expense_notification(): void
    {
        $cat = ExpenseCategory::first() ?? ExpenseCategory::create(['name' => 'Utilities']);
        $expense = Expense::create([
            'reference_no' => 'EXP-2026-' . uniqid(),
            'title' => 'Office Internet Bill',
            'expense_category_id' => $cat->id,
            'amount' => 65.00,
            'expense_date' => now(),
            'user_id' => $this->admin->id,
        ]);

        $notification = $this->notificationService->notifyExpense($expense);

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
        $this->assertEquals('expense', $notification->type);
        $this->assertStringContainsString('65.00', $notification->message);
        $this->assertEquals('secondary', $notification->color);
    }

    /**
     * 7. Test Navbar unread count and Mark All as Read.
     */
    public function test_navbar_notification_count_and_mark_all_read(): void
    {
        // Clear previous notifications for clean state
        Notification::truncate();

        $product = Product::first() ?? Product::factory()->create();
        $this->notificationService->notifyOutOfStock($product);
        $this->notificationService->notifyLowStock($product, 2);

        // Check navbar gets the unread count
        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertViewHas('navbarUnreadCount', 2);

        // Mark all as read
        $markResponse = $this->actingAs($this->admin)->post(route('notifications.markAllRead'));
        $markResponse->assertRedirect();

        $this->assertEquals(0, $this->notificationService->getUnreadCount());

        $responseAfter = $this->actingAs($this->admin)->get(route('dashboard'));
        $responseAfter->assertViewHas('navbarUnreadCount', 0);
    }

    /**
     * 8. Test Individual Notification mark as read.
     */
    public function test_individual_notification_mark_read(): void
    {
        $product = Product::first() ?? Product::factory()->create();
        $notif = $this->notificationService->notifyLowStock($product, 3);
        $this->assertTrue($notif->fresh()->is_unread);

        $this->actingAs($this->admin)->post(route('notifications.markRead', $notif->id));
        $this->assertFalse($notif->fresh()->is_unread);
        $this->assertNotNull($notif->fresh()->read_at);
    }

    /**
     * 9. Test Activity Logs record User, Action, Module, Description, IP Address, Date, Time.
     * And verify human-readable prose matching examples:
     * - "Admin created product"
     * - "Cashier completed sale"
     * - "Manager updated customer"
     * - "Admin deleted supplier"
     */
    public function test_activity_logs_exact_human_readable_descriptions(): void
    {
        // 9a. "Admin created product"
        $this->actingAs($this->admin);
        $log1 = ActivityLoggerService::log(
            action: 'product.create',
            description: "created product 'Wireless Mouse'",
            module: 'products'
        );
        $this->assertEquals('Admin created product \'Wireless Mouse\'', $log1->description);
        $this->assertEquals('products', $log1->module);
        $this->assertEquals($this->admin->id, $log1->user_id);
        $this->assertNotEmpty($log1->formatted_date);
        $this->assertNotEmpty($log1->formatted_time);

        // 9b. "Cashier completed sale"
        $this->actingAs($this->cashier);
        $log2 = ActivityLoggerService::log(
            action: 'sale.complete',
            description: "completed sale INV-2026-000001",
            module: 'sales'
        );
        $this->assertEquals('Cashier completed sale INV-2026-000001', $log2->description);
        $this->assertEquals('sales', $log2->module);
        $this->assertEquals($this->cashier->id, $log2->user_id);

        // 9c. "Manager updated customer"
        $this->actingAs($this->manager);
        $log3 = ActivityLoggerService::log(
            action: 'customer.update',
            description: "updated customer 'John Doe'",
            module: 'customers'
        );
        $this->assertEquals('Manager updated customer \'John Doe\'', $log3->description);
        $this->assertEquals('customers', $log3->module);
        $this->assertEquals($this->manager->id, $log3->user_id);

        // 9d. "Admin deleted supplier"
        $this->actingAs($this->admin);
        $log4 = ActivityLoggerService::log(
            action: 'supplier.delete',
            description: "deleted supplier 'ABC Logistics'",
            module: 'suppliers'
        );
        $this->assertEquals('Admin deleted supplier \'ABC Logistics\'', $log4->description);
        $this->assertEquals('suppliers', $log4->module);
        $this->assertEquals($this->admin->id, $log4->user_id);
    }

    /**
     * 10. Test Activity Logs Search and Filterability.
     */
    public function test_activity_logs_search_and_filterability(): void
    {
        $this->actingAs($this->admin);

        ActivityLog::create([
            'user_id' => $this->admin->id,
            'action' => 'product.create',
            'module' => 'products',
            'description' => "Admin created product 'UniqueSearchWidget'",
            'ip_address' => '192.168.1.100',
        ]);

        ActivityLog::create([
            'user_id' => $this->cashier->id,
            'action' => 'sale.complete',
            'module' => 'sales',
            'description' => "Cashier completed sale INV-9999",
            'ip_address' => '192.168.1.101',
        ]);

        // 10a. Search by description keyword
        $resSearch = $this->get(route('activity-logs.index', ['q' => 'UniqueSearchWidget']));
        $resSearch->assertOk();
        $resSearch->assertSee('UniqueSearchWidget');
        $resSearch->assertDontSee('INV-9999');

        // 10b. Filter by module
        $resModule = $this->get(route('activity-logs.index', ['module' => 'sales']));
        $resModule->assertOk();
        $resModule->assertSee('INV-9999');
        $resModule->assertDontSee('UniqueSearchWidget');

        // 10c. Filter by user
        $resUser = $this->get(route('activity-logs.index', ['user_id' => $this->cashier->id]));
        $resUser->assertOk();
        $resUser->assertSee('INV-9999');
        $resUser->assertDontSee('UniqueSearchWidget');

        // 10d. Filter by action
        $resAction = $this->get(route('activity-logs.index', ['action' => 'product.create']));
        $resAction->assertOk();
        $resAction->assertSee('UniqueSearchWidget');
        $resAction->assertDontSee('INV-9999');
    }
}
