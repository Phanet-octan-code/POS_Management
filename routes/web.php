<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\PosReceiptController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Permission Protected)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active_user'])->group(function () {

    // Dynamic Root Redirect based on user permissions
    Route::get('/', function () {
        $user = Auth::user();
        if ($user->hasPermission('dashboard')) {
            return redirect()->route('dashboard');
        }
        if ($user->hasPermission('pos')) {
            return redirect()->route('pos.index');
        }
        if ($user->hasPermission('products')) {
            return redirect()->route('products.index');
        }
        if ($user->hasPermission('sales')) {
            return redirect()->route('sales.index');
        }
        if ($user->hasPermission('inventory')) {
            return redirect()->route('inventory.index');
        }
        return redirect()->route('profile.index');
    });

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // User Profile & Password Change (accessible to any authenticated active user)
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    });

    // 1. Dashboard: Requires 'dashboard' permission
    Route::middleware('permission:dashboard')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/chart-data', [DashboardController::class, 'chartData'])->name('dashboard.chart-data');
    });

    // 2. POS Terminal & Receipts: Requires 'pos' permission
    Route::middleware('permission:pos')->prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosController::class, 'index'])->name('index');
        Route::get('/search', [PosController::class, 'search'])->name('search');
        Route::get('/barcode-lookup', [PosController::class, 'barcodeLookup'])->name('barcode-lookup');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
        Route::get('/invoice/{sale}', [PosReceiptController::class, 'show'])->name('invoice');
        Route::get('/receipt/{sale}', [PosReceiptController::class, 'show'])->name('receipt.show');
        Route::get('/receipt/{sale}/pdf', [PosReceiptController::class, 'downloadPdf'])->name('receipt.pdf');
        Route::post('/receipt/{sale}/reprint', [PosReceiptController::class, 'reprint'])->name('receipt.reprint');
    });

    // 3. Products Catalog: Requires 'products' permission
    Route::middleware('permission:products')->group(function () {
        Route::get('products/generate-sku', [ProductController::class, 'generateSku'])->name('products.generate-sku');
        Route::get('products/generate-barcode', [ProductController::class, 'generateBarcode'])->name('products.generate-barcode');
        Route::patch('products/{product}/status', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');
        Route::delete('products', [ProductController::class, 'destroyAny'])->name('products.destroy-any');
        Route::resource('products', ProductController::class);
    });

    // 4. Categories: Requires 'categories' permission
    Route::middleware('permission:categories')->group(function () {
        Route::patch('categories/{category}/status', [CategoryController::class, 'toggleStatus'])->name('categories.toggle-status');
        Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit']);
    });

    // 5. Brands: Requires 'brands' permission
    Route::middleware('permission:brands')->group(function () {
        Route::patch('brands/{brand}/status', [BrandController::class, 'toggleStatus'])->name('brands.toggle-status');
        Route::resource('brands', BrandController::class)->except(['create', 'show', 'edit']);
    });

    // 6. Inventory: Requires 'inventory' permission
    Route::middleware('permission:inventory')->prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust');
        Route::post('/quick-add', [InventoryController::class, 'quickAdd'])->name('quick-add');
        Route::post('/quick-remove', [InventoryController::class, 'quickRemove'])->name('quick-remove');
        Route::get('/history', [InventoryController::class, 'history'])->name('history');
    });

    // 7. Customers: Requires 'customers' permission
    Route::middleware('permission:customers')->group(function () {
        Route::patch('customers/{customer}/status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
        Route::resource('customers', CustomerController::class)->except(['create', 'edit']);
    });

    // 8. Suppliers: Requires 'suppliers' permission
    Route::middleware('permission:suppliers')->group(function () {
        Route::patch('suppliers/{supplier}/status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status');
        Route::resource('suppliers', SupplierController::class)->except(['create', 'edit']);
    });

    // 9. Purchases: Requires 'purchases' permission
    Route::middleware('permission:purchases')->group(function () {
        Route::get('purchases/{purchase}/invoice', [PurchaseController::class, 'invoice'])->name('purchases.invoice');
        Route::resource('purchases', PurchaseController::class);
    });

    // 10. Sales & Orders: Requires 'sales' permission
    Route::middleware('permission:sales')->group(function () {
        Route::prefix('sales')->name('sales.')->group(function () {
            Route::get('/', [SaleController::class, 'index'])->name('index');
            Route::get('/{sale}', [SaleController::class, 'show'])->name('show');
        });

        Route::prefix('returns')->name('returns.')->group(function () {
            Route::get('/', [ReturnController::class, 'index'])->name('index');
            Route::get('/create', [ReturnController::class, 'create'])->name('create');
            Route::get('/search-sale', [ReturnController::class, 'searchSale'])->name('search-sale');
            Route::post('/', [ReturnController::class, 'store'])->name('store');
            Route::get('/{return}', [ReturnController::class, 'show'])->name('show');
        });
    });

    // 11. Expenses: Requires 'expenses' permission
    Route::middleware('permission:expenses')->group(function () {
        Route::resource('expenses', ExpenseController::class)->except(['create', 'edit']);
    });

    // 12. Reports: Requires 'reports' permission
    Route::middleware('permission:reports')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
        Route::get('/profit', [ReportController::class, 'profit'])->name('profit');
        Route::get('/inventory', [ReportController::class, 'inventory'])->name('inventory');
        Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
        Route::get('/suppliers', [ReportController::class, 'suppliers'])->name('suppliers');
        Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses');
    });

    // 13. Users & Roles Management: Requires 'users' permission
    Route::middleware('permission:users')->group(function () {
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::resource('users', UserController::class)->except(['create', 'edit']);
        Route::resource('roles', RoleController::class)->except(['create', 'edit']);
    });

    // System Notifications (accessible to all authenticated users)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllRead');
        Route::post('/{id}/mark-read', [NotificationController::class, 'markRead'])->name('markRead');
    });

    // 14. Settings & System Administration: Requires 'settings' permission
    Route::middleware('permission:settings')->group(function () {
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingController::class, 'index'])->name('index');
            Route::post('/', [SettingController::class, 'update'])->name('update');
        });
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

    // Firebase Cloud Integration & Synchronization
    Route::prefix('firebase')->name('firebase.')->group(function () {
        Route::get('/status', [FirebaseController::class, 'status'])->name('status');
        Route::get('/export-payload', [FirebaseController::class, 'exportPayload'])->name('export-payload');
        Route::post('/sync', [FirebaseController::class, 'sync'])->name('sync');
    });
});
