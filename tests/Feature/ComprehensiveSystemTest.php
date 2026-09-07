<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ReturnOrder;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PosService;
use App\Services\ReportService;
use App\Services\ReturnService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComprehensiveSystemTest extends TestCase
{
    protected User $admin;
    protected User $manager;
    protected User $cashier;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@pos.com')->first();
        $this->manager = User::where('email', 'manager@pos.com')->first();
        $this->cashier = User::where('email', 'cashier@pos.com')->first();
        $this->staff = User::where('email', 'staff@pos.com')->first();
    }

    /**
     * MODULE 1: AUTHENTICATION
     */
    public function test_01_authentication_workflows(): void
    {
        // 1. Guest can view login page
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // 2. Invalid credentials fail
        $response = $this->post(route('login.post'), [
            'email' => 'admin@pos.com',
            'password' => 'wrong-password',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        // 3. Valid credentials succeed
        $response = $this->post(route('login.post'), [
            'email' => 'admin@pos.com',
            'password' => 'password',
        ]);
        $response->assertRedirect();
        $this->assertAuthenticatedAs($this->admin);

        // 4. Logout succeeds
        $response = $this->actingAs($this->admin)->post(route('logout'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();

        // 5. Deactivated user cannot authenticate
        $inactiveUser = User::create([
            'name' => 'Inactive Guy',
            'email' => 'inactive_' . uniqid() . '@pos.com',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);
        $response = $this->post(route('login.post'), [
            'email' => $inactiveUser->email,
            'password' => 'password',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * MODULE 2: ROLES
     */
    public function test_02_roles_hierarchy_and_relations(): void
    {
        $this->assertTrue($this->admin->isAdmin());
        $this->assertTrue($this->manager->isManager());
        $this->assertTrue($this->cashier->isCashier());
        $this->assertTrue($this->staff->isStaff());

        $this->assertDatabaseHas('roles', ['slug' => 'admin']);
        $this->assertDatabaseHas('roles', ['slug' => 'manager']);
        $this->assertDatabaseHas('roles', ['slug' => 'cashier']);
        $this->assertDatabaseHas('roles', ['slug' => 'staff']);
    }

    /**
     * MODULE 3: PERMISSIONS
     */
    public function test_03_permissions_enforcement(): void
    {
        // Admin has all permissions bypass
        $this->actingAs($this->admin);
        $this->get(route('settings.index'))->assertStatus(200);
        $this->get(route('users.index'))->assertStatus(200);

        // Cashier has POS and Sales, but NOT Settings or Reports or Users
        $this->actingAs($this->cashier);
        $this->get(route('pos.index'))->assertStatus(200);
        $this->get(route('settings.index'))->assertStatus(403);
        $this->get(route('reports.index'))->assertStatus(403);
        $this->get(route('users.index'))->assertStatus(403);

        // Staff has products and inventory, but NOT settings
        $this->actingAs($this->staff);
        $this->get(route('products.index'))->assertStatus(200);
        $this->get(route('inventory.index'))->assertStatus(200);
        $this->get(route('settings.index'))->assertStatus(403);
    }

    /**
     * MODULE 4: DASHBOARD
     */
    public function test_04_dashboard_metrics_and_charts(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertViewHasAll([
            'summary',
            'salesTrend',
            'monthlySales',
            'monthlyProfit',
            'recentSales',
            'topSellingProducts',
            'lowStockProducts',
        ]);

        // AJAX Chart data endpoint
        $chartResponse = $this->getJson(route('dashboard.chart-data', ['filter' => '7days']));
        $chartResponse->assertStatus(200);
        $chartResponse->assertJsonStructure([
            'success',
            'trend' => ['labels', 'data', 'title'],
        ]);
    }

    /**
     * MODULE 5: PRODUCTS
     */
    public function test_05_products_crud_sku_and_barcode(): void
    {
        $this->actingAs($this->admin);

        // SKU generation
        $skuRes = $this->getJson(route('products.generate-sku', ['name' => 'Ultra Smart Watch Pro']));
        $skuRes->assertStatus(200);
        $skuRes->assertJsonStructure(['sku']);

        // Barcode generation
        $barcodeRes = $this->getJson(route('products.generate-barcode'));
        $barcodeRes->assertStatus(200);
        $barcodeRes->assertJsonStructure(['barcode']);

        $category = Category::first();
        $brand = Brand::first();

        // Create product
        $sku = 'TEST-PROD-' . uniqid();
        $createRes = $this->post(route('products.store'), [
            'name' => 'Premium Wireless Headphones',
            'sku' => $sku,
            'barcode' => '893' . rand(100000000, 999999999),
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'cost_price' => 45.00,
            'selling_price' => 89.99,
            'stock_quantity' => 50,
            'alert_quantity' => 10,
            'unit' => 'pcs',
            'is_active' => 1,
        ]);

        $createRes->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['sku' => $sku]);

        $product = Product::where('sku', $sku)->first();

        // Status toggle
        $toggleRes = $this->patchJson(route('products.toggle-status', $product));
        $toggleRes->assertStatus(200);
        $product->refresh();
        $this->assertFalse((bool) $product->is_active);
    }

    /**
     * MODULE 6: CATEGORIES
     */
    public function test_06_categories_crud(): void
    {
        $this->actingAs($this->admin);

        $catName = 'Peripherals ' . uniqid();
        $res = $this->post(route('categories.store'), [
            'name' => $catName,
            'description' => 'Keyboards and mice',
        ]);
        $res->assertRedirect();
        $this->assertDatabaseHas('categories', ['name' => $catName]);

        $category = Category::where('name', $catName)->first();
        $toggleRes = $this->patch(route('categories.toggle-status', $category));
        $toggleRes->assertRedirect();
    }

    /**
     * MODULE 7: BRANDS
     */
    public function test_07_brands_crud(): void
    {
        $this->actingAs($this->admin);

        $brandName = 'Sennheiser ' . uniqid();
        $res = $this->post(route('brands.store'), [
            'name' => $brandName,
            'description' => 'Audio gear',
        ]);
        $res->assertRedirect();
        $this->assertDatabaseHas('brands', ['name' => $brandName]);

        $brand = Brand::where('name', $brandName)->first();
        $toggleRes = $this->patch(route('brands.toggle-status', $brand));
        $toggleRes->assertRedirect();
    }

    /**
     * MODULE 8: CUSTOMERS
     */
    public function test_08_customers_crud(): void
    {
        $this->actingAs($this->admin);

        $phone = '+1555' . rand(1000000, 9999999);
        $res = $this->post(route('customers.store'), [
            'name' => 'Robert Tester',
            'phone' => $phone,
            'email' => 'robert_' . uniqid() . '@example.com',
            'type' => 'Regular',
            'address' => '456 Test Street',
        ]);
        $res->assertRedirect();
        $this->assertDatabaseHas('customers', ['phone' => $phone]);

        $customer = Customer::where('phone', $phone)->first();
        $toggleRes = $this->patch(route('customers.toggle-status', $customer));
        $toggleRes->assertRedirect();
    }

    /**
     * MODULE 9: SUPPLIERS
     */
    public function test_09_suppliers_crud(): void
    {
        $this->actingAs($this->admin);

        $name = 'Apex Logistics ' . uniqid();
        $res = $this->post(route('suppliers.store'), [
            'name' => $name,
            'company_name' => 'Apex Corp Ltd',
            'phone' => '+1555' . rand(1000000, 9999999),
            'email' => 'apex_' . uniqid() . '@example.com',
            'address' => 'Terminal 9 Warehouse',
        ]);
        $res->assertRedirect();
        $this->assertDatabaseHas('suppliers', ['name' => $name]);

        $supplier = Supplier::where('name', $name)->first();
        $this->assertEquals('Apex Corp Ltd', $supplier->company);
    }

    /**
     * MODULE 10: INVENTORY
     */
    public function test_10_inventory_adjustments_and_movements(): void
    {
        $this->actingAs($this->admin);

        $product = Product::where('stock_quantity', '>', 10)->first();
        $initialStock = $product->stock_quantity;

        // Quick add
        $resAdd = $this->post(route('inventory.quick-add'), [
            'product_id' => $product->id,
            'quantity' => 5,
            'notes' => 'Received sample batch',
        ]);
        $resAdd->assertRedirect();
        $product->refresh();
        $this->assertEquals($initialStock + 5, $product->stock_quantity);

        // Quick remove
        $resRem = $this->post(route('inventory.quick-remove'), [
            'product_id' => $product->id,
            'quantity' => 3,
            'notes' => 'Damaged during unpack',
        ]);
        $resRem->assertRedirect();
        $product->refresh();
        $this->assertEquals($initialStock + 2, $product->stock_quantity);

        // Movement record created
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
    }

    /**
     * MODULE 11: PURCHASES
     */
    public function test_11_purchases_workflow(): void
    {
        $this->actingAs($this->admin);

        $supplier = Supplier::first();
        $product = Product::first();

        $refNo = 'PO-TEST-' . uniqid();
        $res = $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'reference_no' => $refNo,
            'purchase_date' => now()->toDateString(),
            'status' => 'received',
            'payment_status' => 'paid',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 10,
                    'unit_cost' => 8.00,
                ],
            ],
            'notes' => 'Stock replenishment order',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('purchases', ['reference_no' => $refNo]);

        $purchase = Purchase::where('reference_no', $refNo)->first();
        $this->assertEquals($refNo, $purchase->purchase_number);
    }

    /**
     * MODULE 12: POS TERMINAL
     */
    public function test_12_pos_search_lookup_and_checkout(): void
    {
        $this->actingAs($this->cashier);

        $product = Product::where('stock_quantity', '>=', 5)->first();

        // 1. Search endpoint
        $searchRes = $this->getJson(route('pos.search', ['query' => $product->name]));
        $searchRes->assertStatus(200);

        // 2. Barcode lookup endpoint
        $barcodeRes = $this->getJson(route('pos.barcode-lookup', ['barcode' => $product->barcode]));
        $barcodeRes->assertStatus(200);
        $barcodeRes->assertJsonFragment(['sku' => $product->sku]);

        // 3. Checkout transaction
        $initialStock = $product->stock_quantity;
        $customer = Customer::first();

        $checkoutRes = $this->postJson(route('pos.checkout'), [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => $product->selling_price,
                    'discount' => 0,
                ],
            ],
            'subtotal' => $product->selling_price * 2,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $product->selling_price * 2,
            'paid_amount' => $product->selling_price * 2,
            'payment_method' => 'cash',
        ]);

        $checkoutRes->assertStatus(200);
        $checkoutRes->assertJsonStructure([
            'success',
            'sale_id',
            'invoice_no',
        ]);

        // Stock decreased atomically
        $product->refresh();
        $this->assertEquals($initialStock - 2, $product->stock_quantity);
    }

    /**
     * MODULE 13: SALES MANAGEMENT
     */
    public function test_13_sales_management_index_and_details(): void
    {
        $this->actingAs($this->admin);

        $sale = Sale::first();
        $this->assertNotNull($sale);

        $resList = $this->get(route('sales.index'));
        $resList->assertStatus(200);
        $resList->assertViewHas('sales');

        $resShow = $this->get(route('sales.show', $sale));
        $resShow->assertStatus(200);
        $resShow->assertViewHas('sale');
    }

    /**
     * MODULE 14: PAYMENTS
     */
    public function test_14_polymorphic_payments(): void
    {
        $sale = Sale::first();
        $payRef = 'PAY-TEST-' . uniqid();

        $payment = Payment::create([
            'payment_number' => $payRef,
            'payable_type' => Sale::class,
            'payable_id' => $sale->id,
            'amount' => 15.00,
            'payment_method' => 'aba',
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('payments', ['payment_number' => $payRef]);
        $this->assertInstanceOf(Sale::class, $payment->payable);
    }

    /**
     * MODULE 15: RECEIPTS (58mm, 80mm, A4, PDF)
     */
    public function test_15_receipt_rendering_and_pdf(): void
    {
        $this->actingAs($this->cashier);

        $sale = Sale::with('items')->first();

        // 80mm receipt view
        $res80 = $this->get(route('pos.receipt.show', ['sale' => $sale->id, 'format' => '80mm']));
        $res80->assertStatus(200);

        // 58mm receipt view
        $res58 = $this->get(route('pos.receipt.show', ['sale' => $sale->id, 'format' => '58mm']));
        $res58->assertStatus(200);

        // A4 receipt view
        $resA4 = $this->get(route('pos.receipt.show', ['sale' => $sale->id, 'format' => 'a4']));
        $resA4->assertStatus(200);

        // Reprint counter increment
        $initialReprint = $sale->reprint_count;
        $reprintRes = $this->postJson(route('pos.receipt.reprint', $sale));
        $reprintRes->assertStatus(200);
        $sale->refresh();
        $this->assertEquals($initialReprint + 1, $sale->reprint_count);

        // PDF download
        $pdfRes = $this->get(route('pos.receipt.pdf', ['sale' => $sale->id, 'format' => '80mm']));
        $pdfRes->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $pdfRes->headers->get('content-type'));
    }

    /**
     * MODULE 16: RETURNS
     */
    public function test_16_returns_process_and_stock_restoration(): void
    {
        $this->actingAs($this->admin);

        $product = Product::where('stock_quantity', '>=', 10)->first();
        $initialStock = $product->stock_quantity;

        // Process a dedicated sale of 2 items
        $posService = app(PosService::class);
        $sale = $posService->processSale([
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => $product->selling_price,
                    'discount' => 0,
                    'subtotal' => $product->selling_price * 2,
                ],
            ],
            'subtotal' => $product->selling_price * 2,
            'total_amount' => $product->selling_price * 2,
            'paid_amount' => $product->selling_price * 2,
            'payment_method' => 'cash',
        ]);
        $product->refresh();
        $stockAfterSale = $product->stock_quantity;
        $this->assertEquals($initialStock - 2, $stockAfterSale);

        $saleItem = $sale->items->first();

        // Search sale AJAX
        $searchRes = $this->getJson(route('returns.search-sale', ['invoice_no' => $sale->invoice_no]));
        $searchRes->assertStatus(200);
        $searchRes->assertJsonFragment(['invoice_no' => $sale->invoice_no]);

        // Process return of 1 item
        $returnRes = $this->post(route('returns.store'), [
            'sale_id' => $sale->id,
            'reason' => 'Customer changed mind',
            'items' => [
                [
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => $saleItem->unit_price,
                ],
            ],
        ]);

        $returnRes->assertRedirect();
        $product->refresh();
        $this->assertEquals($stockAfterSale + 1, $product->stock_quantity);

        // Stock movement recorded for return
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'return',
            'quantity' => 1,
        ]);
    }

    /**
     * MODULE 17: EXPENSES
     */
    public function test_17_expenses_management(): void
    {
        $this->actingAs($this->admin);

        $cat = ExpenseCategory::first() ?? ExpenseCategory::create(['name' => 'Utilities']);
        $ref = 'EXP-TEST-' . uniqid();

        $res = $this->post(route('expenses.store'), [
            'title' => 'High Speed Fiber Internet',
            'expense_category_id' => $cat->id,
            'amount' => 85.00,
            'expense_date' => now()->toDateString(),
            'reference_no' => $ref,
            'notes' => 'Monthly broadband',
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('expenses', ['title' => 'High Speed Fiber Internet']);
    }

    /**
     * MODULE 18: REPORTS (ALL 7 REPORTS & EXPORTS)
     */
    public function test_18_all_seven_reports_and_exports(): void
    {
        $this->actingAs($this->admin);

        // 1. Sales Report
        $this->get(route('reports.sales'))->assertStatus(200);
        $csvSale = $this->get(route('reports.sales', ['export' => 'csv']));
        $csvSale->assertStatus(200);

        // 2. Purchase Report (Tests the reference_no fix)
        $this->get(route('reports.purchases', ['search' => 'PO']))->assertStatus(200);
        $csvPurch = $this->get(route('reports.purchases', ['export' => 'csv']));
        $csvPurch->assertStatus(200);

        // 3. Profit Report (GP = Revenue - Cost, NP = GP - Expenses)
        $this->get(route('reports.profit'))->assertStatus(200);

        // 4. Inventory Report
        $this->get(route('reports.inventory'))->assertStatus(200);

        // 5. Customer Report
        $this->get(route('reports.customers'))->assertStatus(200);

        // 6. Supplier Report (Tests company_name search and sorting fix)
        $this->get(route('reports.suppliers', ['search' => 'Apex', 'sort_by' => 'company']))->assertStatus(200);

        // 7. Expense Report
        $this->get(route('reports.expenses'))->assertStatus(200);
    }

    /**
     * MODULE 19: USERS MANAGEMENT
     */
    public function test_19_users_crud_and_status_toggle(): void
    {
        $this->actingAs($this->admin);

        $email = 'cashier_new_' . uniqid() . '@pos.com';
        $res = $this->post(route('users.store'), [
            'name' => 'New Cashier',
            'email' => $email,
            'phone' => '+1555' . rand(1000000, 9999999),
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'roles' => [$this->cashier->roles()->first()->id],
            'is_active' => 1,
        ]);

        $res->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => $email]);

        $user = User::where('email', $email)->first();

        // Toggle active status
        $toggleRes = $this->patch(route('users.toggle-status', $user));
        $toggleRes->assertRedirect();
        $user->refresh();
        $this->assertFalse((bool) $user->is_active);

        // Password reset by admin
        $pwRes = $this->post(route('users.reset-password', $user), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $pwRes->assertRedirect();
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /**
     * MODULE 20: SETTINGS DYNAMIC PROPAGATION
     */
    public function test_20_settings_update_and_propagation(): void
    {
        $this->actingAs($this->admin);

        $customStore = 'Super Retail POS ' . uniqid();
        $res = $this->post(route('settings.update'), [
            'settings' => [
                'store_name' => $customStore,
                'store_phone' => '+1 (555) 777-9999',
                'currency_symbol' => '$',
                'store_tax' => '8.5',
                'invoice_prefix' => 'INV-',
                'receipt_size' => '80mm',
                'enable_barcode' => '1',
                'enable_customer' => '1',
                'enable_sound' => '1',
            ],
        ]);

        $res->assertRedirect();
        $this->assertEquals($customStore, Setting::get('store_name'));

        // Check global view composer receives setting
        $dash = $this->get(route('dashboard'));
        $dash->assertSee($customStore);
    }

    /**
     * MODULE 21: NOTIFICATIONS
     */
    public function test_21_notifications_center_and_mark_read(): void
    {
        $this->actingAs($this->admin);

        $notifService = app(NotificationService::class);
        $product = Product::first();
        $product->update(['stock_quantity' => 1, 'alert_quantity' => 5]);

        $notif = $notifService->notifyLowStock($product);
        $this->assertDatabaseHas('notifications', ['id' => $notif->id]);

        // Notification index page
        $res = $this->get(route('notifications.index'));
        $res->assertStatus(200);

        // Mark single notification as read
        $markRes = $this->postJson(route('notifications.markRead', $notif->id));
        $markRes->assertStatus(200);
        $notif->refresh();
        $this->assertNotNull($notif->read_at);

        // Mark all as read
        $allRes = $this->post(route('notifications.markAllRead'));
        $allRes->assertRedirect();
    }

    /**
     * MODULE 22: ACTIVITY LOGS
     */
    public function test_22_activity_logs_recording_and_filters(): void
    {
        $this->actingAs($this->admin);

        // View activity logs page
        $res = $this->get(route('activity-logs.index'));
        $res->assertStatus(200);

        // Verify filter by module and search
        $filterRes = $this->get(route('activity-logs.index', [
            'module' => 'sales',
            'search' => 'sale',
        ]));
        $filterRes->assertStatus(200);
    }
}
