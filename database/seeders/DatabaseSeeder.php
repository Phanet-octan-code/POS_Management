<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles Definition
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], [
            'name' => 'Admin',
            'description' => 'Full administrative access to entire POS system',
        ]);

        $managerRole = Role::firstOrCreate(['slug' => 'manager'], [
            'name' => 'Manager',
            'description' => 'Store manager with access to dashboard, inventory, sales, purchases, customers, suppliers and reports',
        ]);

        $cashierRole = Role::firstOrCreate(['slug' => 'cashier'], [
            'name' => 'Cashier',
            'description' => 'POS Terminal, sales orders and customer lookup access',
        ]);

        $staffRole = Role::firstOrCreate(['slug' => 'staff'], [
            'name' => 'Staff',
            'description' => 'Store staff with access to products catalog, inventory stock and customers',
        ]);

        // 2. The 14 Standard Permissions Definition
        $canonicalPermissions = [
            'dashboard'  => ['name' => 'Dashboard', 'module' => 'Core', 'description' => 'Access executive dashboard, statistics and sales charts'],
            'pos'        => ['name' => 'POS', 'module' => 'Sales', 'description' => 'Operate POS checkout, scan barcodes, take payments and print receipts'],
            'products'   => ['name' => 'Products', 'module' => 'Catalog', 'description' => 'Manage inventory products, SKU generation, pricing and barcodes'],
            'categories' => ['name' => 'Categories', 'module' => 'Catalog', 'description' => 'Manage product categories and classifications'],
            'brands'     => ['name' => 'Brands', 'module' => 'Catalog', 'description' => 'Manage product brands and manufacturers'],
            'customers'  => ['name' => 'Customers', 'module' => 'Relations', 'description' => 'Manage customer records, credit info and purchase history'],
            'suppliers'  => ['name' => 'Suppliers', 'module' => 'Procurement', 'description' => 'Manage vendor contacts, supplier orders and invoices'],
            'purchases'  => ['name' => 'Purchases', 'module' => 'Procurement', 'description' => 'Create stock purchase orders, receive inventory and supplier invoices'],
            'sales'      => ['name' => 'Sales', 'module' => 'Sales', 'description' => 'View sale transaction logs, invoices, and process customer returns'],
            'inventory'  => ['name' => 'Inventory', 'module' => 'Catalog', 'description' => 'Stock adjustments, quick addition/removal, and movement ledger'],
            'expenses'   => ['name' => 'Expenses', 'module' => 'Finance', 'description' => 'Track business expenses, overheads, and financial outflows'],
            'reports'    => ['name' => 'Reports', 'module' => 'Analytics', 'description' => 'Access sales, profit, purchase, inventory and expense reports with exports'],
            'users'      => ['name' => 'Users', 'module' => 'Administration', 'description' => 'Manage staff accounts, assign roles, reset passwords and toggle status'],
            'settings'   => ['name' => 'Settings', 'module' => 'Administration', 'description' => 'Configure store details, tax rates, currency and system options'],
        ];

        $createdPermissions = [];
        foreach ($canonicalPermissions as $slug => $meta) {
            $perm = Permission::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $meta['name'],
                    'module' => $meta['module'],
                    'description' => $meta['description'],
                ]
            );
            $createdPermissions[$slug] = $perm->id;
        }

        // Assign Permissions to Roles:
        // Admin: All 14 permissions
        $adminRole->permissions()->sync(array_values($createdPermissions));

        // Manager: Dashboard, POS, Products, Categories, Brands, Customers, Suppliers, Purchases, Sales, Inventory, Expenses, Reports
        $managerRole->permissions()->sync([
            $createdPermissions['dashboard'],
            $createdPermissions['pos'],
            $createdPermissions['products'],
            $createdPermissions['categories'],
            $createdPermissions['brands'],
            $createdPermissions['customers'],
            $createdPermissions['suppliers'],
            $createdPermissions['purchases'],
            $createdPermissions['sales'],
            $createdPermissions['inventory'],
            $createdPermissions['expenses'],
            $createdPermissions['reports'],
        ]);

        // Cashier: POS, Sales, Customers
        $cashierRole->permissions()->sync([
            $createdPermissions['pos'],
            $createdPermissions['sales'],
            $createdPermissions['customers'],
        ]);

        // Staff: Products, Categories, Brands, Inventory, Customers
        $staffRole->permissions()->sync([
            $createdPermissions['products'],
            $createdPermissions['categories'],
            $createdPermissions['brands'],
            $createdPermissions['inventory'],
            $createdPermissions['customers'],
        ]);

        // 3. Four Dedicated Test Users
        // Admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@pos.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'phone' => '+1 (555) 019-1000',
                'is_active' => true,
            ]
        );
        $admin->roles()->sync([$adminRole->id]);

        // Manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@pos.com'],
            [
                'name' => 'Sarah Manager',
                'password' => Hash::make('password'),
                'phone' => '+1 (555) 019-2000',
                'is_active' => true,
            ]
        );
        $manager->roles()->sync([$managerRole->id]);

        // Cashier
        $cashier = User::updateOrCreate(
            ['email' => 'cashier@pos.com'],
            [
                'name' => 'John Cashier',
                'password' => Hash::make('password'),
                'phone' => '+1 (555) 019-3000',
                'is_active' => true,
            ]
        );
        $cashier->roles()->sync([$cashierRole->id]);

        // Staff
        $staff = User::updateOrCreate(
            ['email' => 'staff@pos.com'],
            [
                'name' => 'Michael Staff',
                'password' => Hash::make('password'),
                'phone' => '+1 (555) 019-4000',
                'is_active' => true,
            ]
        );
        $staff->roles()->sync([$staffRole->id]);

        // 4. Categories & Brands
        $bev = Category::firstOrCreate(['slug' => 'beverages'], ['name' => 'Beverages', 'description' => 'Cold drinks, juices & coffee']);
        $snk = Category::firstOrCreate(['slug' => 'snacks'], ['name' => 'Snacks & Confectionery']);
        $elec = Category::firstOrCreate(['slug' => 'electronics'], ['name' => 'Accessories & Electronics']);
        $stat = Category::firstOrCreate(['slug' => 'stationery'], ['name' => 'Office & Stationery']);

        $genericBrand = Brand::firstOrCreate(['slug' => 'generic'], ['name' => 'Generic']);
        $cokeBrand = Brand::firstOrCreate(['slug' => 'coca-cola'], ['name' => 'Coca Cola']);
        $logiBrand = Brand::firstOrCreate(['slug' => 'logitech'], ['name' => 'Logitech']);

        // 5. Sample Products
        $products = [
            [
                'category_id' => $elec->id,
                'brand_id' => $logiBrand->id,
                'name' => 'Logitech Wireless Mouse M185',
                'sku' => 'LOG-M185',
                'barcode' => '097855078583',
                'cost_price' => 9.50,
                'selling_price' => 15.99,
                'stock_quantity' => 25,
                'alert_quantity' => 5,
                'unit' => 'pcs',
            ],
            [
                'category_id' => $bev->id,
                'brand_id' => $cokeBrand->id,
                'name' => 'Coca-Cola Classic 330ml Can',
                'sku' => 'COKE-330ML',
                'barcode' => '5449000000996',
                'cost_price' => 0.55,
                'selling_price' => 1.25,
                'stock_quantity' => 150,
                'alert_quantity' => 30,
                'unit' => 'can',
            ],
            [
                'category_id' => $bev->id,
                'brand_id' => $genericBrand->id,
                'name' => 'Espresso Arabica Dark Roast 250g',
                'sku' => 'COFF-ARB-250',
                'barcode' => '8901030018123',
                'cost_price' => 4.20,
                'selling_price' => 7.99,
                'stock_quantity' => 40,
                'alert_quantity' => 10,
                'unit' => 'pack',
            ],
            [
                'category_id' => $snk->id,
                'brand_id' => $genericBrand->id,
                'name' => 'Artisan Potato Chips Salted 150g',
                'sku' => 'CHIP-SLT-150',
                'barcode' => '7610095111005',
                'cost_price' => 1.10,
                'selling_price' => 2.49,
                'stock_quantity' => 60,
                'alert_quantity' => 15,
                'unit' => 'bag',
            ],
            [
                'category_id' => $stat->id,
                'brand_id' => $genericBrand->id,
                'name' => 'Thermal Receipt Paper Roll 80mm x 80mm',
                'sku' => 'PAPR-ROLL-80',
                'barcode' => '6921345678901',
                'cost_price' => 0.80,
                'selling_price' => 1.99,
                'stock_quantity' => 12,
                'alert_quantity' => 15,
                'unit' => 'roll',
            ],
            [
                'category_id' => $elec->id,
                'brand_id' => $genericBrand->id,
                'name' => 'Braided USB-C Fast Charging Cable 1.5m',
                'sku' => 'CABL-USBC-15',
                'barcode' => '7891234567890',
                'cost_price' => 2.50,
                'selling_price' => 6.50,
                'stock_quantity' => 8,
                'alert_quantity' => 10,
                'unit' => 'pcs',
            ],
        ];

        foreach ($products as $p) {
            $slug = Str::slug($p['name']);
            if (! Product::withTrashed()->where('slug', $slug)->exists() && ! Product::withTrashed()->where('sku', $p['sku'])->exists()) {
                Product::create(array_merge($p, ['slug' => $slug, 'is_active' => true]));
            }
        }

        // 6. Customers & Suppliers
        Customer::firstOrCreate(['name' => 'Alice Johnson'], [
            'phone' => '+1 (555) 432-1098',
            'email' => 'alice@example.com',
            'address' => '742 Evergreen Terrace',
            'points' => 35,
            'total_spent' => 350.00,
            'is_active' => true,
        ]);

        Supplier::firstOrCreate(['name' => 'Global Beverage Distributors'], [
            'company_name' => 'Global Bev Ltd',
            'phone' => '+1 (555) 888-9999',
            'email' => 'orders@globalbev.com',
            'address' => 'Industrial Logistics Park, Dock 4',
            'tax_number' => 'US-TAX-8921471',
            'is_active' => true,
        ]);

        // 7. Expense Categories
        $this->call(ExpenseCategorySeeder::class);

        // 8. Settings
        Setting::set('store_name', 'OmniPOS Superstore');
        Setting::set('store_phone', '+1 (555) 019-2831');
        Setting::set('store_address', '100 Downtown Boulevard, Metropolis');
        Setting::set('currency_symbol', '$');
        Setting::set('default_tax_rate', '5.0');
        Setting::set('invoice_prefix', 'INV-');
        Setting::set('receipt_footer', 'Thank you for shopping with us! Returns accepted within 14 days with original receipt.');
    }
}
