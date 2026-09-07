<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ReturnOrder;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebaseService
{
    protected string $apiKey;
    protected string $projectId;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = (string) config('firebase.api_key');
        $this->projectId = (string) config('firebase.project_id');
        $this->baseUrl = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents";
    }

    /**
     * Test connection to Firebase Firestore REST API.
     */
    public function testConnection(): array
    {
        try {
            $url = "{$this->baseUrl}/_health/ping?key={$this->apiKey}";
            $payload = [
                'fields' => [
                    'status' => ['stringValue' => 'connected'],
                    'timestamp' => ['stringValue' => now()->toIso8601String()],
                    'app' => ['stringValue' => 'POS Management System'],
                ]
            ];

            $response = Http::timeout(4)->patch($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'project' => $this->projectId,
                    'status' => 'connected',
                    'message' => 'Successfully connected to Firebase Firestore project (' . $this->projectId . ')',
                ];
            }

            return [
                'success' => false,
                'project' => $this->projectId,
                'status' => 'error',
                'message' => 'Firebase returned HTTP ' . $response->status() . ': ' . $response->body(),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'project' => $this->projectId,
                'status' => 'unreachable',
                'message' => 'Firebase connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Save/patch a document in a Firestore collection.
     */
    public function saveDocument(string $collection, string $documentId, array $data): bool
    {
        try {
            $url = "{$this->baseUrl}/{$collection}/{$documentId}?key={$this->apiKey}";
            $firestoreFields = $this->toFirestoreFields($data);

            $response = Http::timeout(4)->patch($url, [
                'fields' => $firestoreFields,
            ]);

            if (!$response->successful()) {
                Log::warning("[Firebase] Failed to write document to {$collection}/{$documentId}: " . $response->body());
                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning("[Firebase] Connection error while writing to {$collection}/{$documentId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a document from a Firestore collection.
     */
    public function deleteDocument(string $collection, string $documentId): bool
    {
        try {
            $url = "{$this->baseUrl}/{$collection}/{$documentId}?key={$this->apiKey}";

            $response = Http::timeout(4)->delete($url);

            if (!$response->successful()) {
                Log::warning("[Firebase] Failed to delete document {$collection}/{$documentId}: " . $response->body());
                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning("[Firebase] Connection error while deleting {$collection}/{$documentId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generic dispatcher to store any supported Eloquent model into Firestore.
     */
    public function storeModel(Model $model): bool
    {
        return match (true) {
            $model instanceof Sale => $this->storeSale($model),
            $model instanceof Product => $this->storeProduct($model),
            $model instanceof Category => $this->storeCategory($model),
            $model instanceof Brand => $this->storeBrand($model),
            $model instanceof Customer => $this->storeCustomer($model),
            $model instanceof Supplier => $this->storeSupplier($model),
            $model instanceof Purchase => $this->storePurchase($model),
            $model instanceof Expense => $this->storeExpense($model),
            $model instanceof ReturnOrder => $this->storeReturn($model),
            $model instanceof StockMovement => $this->storeStockMovement($model),
            $model instanceof Setting => $this->storeSetting($model),
            $model instanceof ActivityLog => $this->storeActivityLog($model),
            $model instanceof Notification => $this->storeNotification($model),
            $model instanceof User => $this->storeUser($model),
            default => false,
        };
    }

    /**
     * Generic dispatcher to delete any supported Eloquent model from Firestore.
     */
    public function deleteModel(Model $model): bool
    {
        $collection = match (true) {
            $model instanceof Sale => 'sales',
            $model instanceof Product => 'products',
            $model instanceof Category => 'categories',
            $model instanceof Brand => 'brands',
            $model instanceof Customer => 'customers',
            $model instanceof Supplier => 'suppliers',
            $model instanceof Purchase => 'purchases',
            $model instanceof Expense => 'expenses',
            $model instanceof ReturnOrder => 'returns',
            $model instanceof StockMovement => 'stock_movements',
            $model instanceof Setting => 'settings',
            $model instanceof ActivityLog => 'activity_logs',
            $model instanceof Notification => 'notifications',
            $model instanceof User => 'users',
            default => null,
        };

        if (!$collection) {
            return false;
        }

        $docId = match (true) {
            $model instanceof Sale => "sale_{$model->id}",
            $model instanceof Product => "product_{$model->id}",
            $model instanceof Category => "cat_{$model->id}",
            $model instanceof Brand => "brand_{$model->id}",
            $model instanceof Customer => "customer_{$model->id}",
            $model instanceof Supplier => "supplier_{$model->id}",
            $model instanceof Purchase => "purchase_{$model->id}",
            $model instanceof Expense => "expense_{$model->id}",
            $model instanceof ReturnOrder => "return_{$model->id}",
            $model instanceof StockMovement => "movement_{$model->id}",
            $model instanceof Setting => "setting_" . ($model->key ?? $model->id),
            $model instanceof ActivityLog => "log_{$model->id}",
            $model instanceof Notification => "notif_{$model->id}",
            $model instanceof User => "user_{$model->id}",
            default => (string) $model->getKey(),
        };

        return $this->deleteDocument($collection, $docId);
    }

    /**
     * Store completed Sale in Firebase Firestore.
     */
    public function storeSale(Sale $sale): bool
    {
        $sale->loadMissing(['items.product', 'customer', 'user']);

        $items = [];
        foreach ($sale->items as $item) {
            $items[] = [
                'product_id' => (int) $item->product_id,
                'name' => (string) ($item->product->name ?? 'Unknown Item'),
                'sku' => (string) ($item->product->sku ?? ''),
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) $item->discount,
                'subtotal' => (float) $item->subtotal,
            ];
        }

        $payload = [
            'id' => (int) $sale->id,
            'invoice_no' => (string) $sale->invoice_no,
            'sale_date' => $sale->sale_date ? $sale->sale_date->toIso8601String() : now()->toIso8601String(),
            'subtotal' => (float) $sale->subtotal,
            'tax_amount' => (float) $sale->tax_amount,
            'discount_amount' => (float) $sale->discount_amount,
            'total_amount' => (float) $sale->total_amount,
            'paid_amount' => (float) $sale->paid_amount,
            'change_amount' => (float) $sale->change_amount,
            'due_amount' => (float) $sale->due_amount,
            'payment_method' => (string) $sale->payment_method,
            'payment_status' => (string) $sale->payment_status,
            'customer_name' => (string) ($sale->customer?->name ?? 'Walk-in Customer'),
            'cashier_name' => (string) ($sale->user?->name ?? 'Cashier'),
            'notes' => (string) ($sale->notes ?? ''),
            'items' => $items,
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->saveDocument('sales', "sale_{$sale->id}", $payload);
    }

    /**
     * Store Product in Firebase Firestore.
     */
    public function storeProduct(Product $product): bool
    {
        $product->loadMissing(['category', 'brand']);

        $payload = [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => (string) ($product->sku ?? ''),
            'barcode' => (string) ($product->barcode ?? ''),
            'selling_price' => (float) $product->selling_price,
            'purchase_price' => (float) ($product->purchase_price ?? $product->cost_price ?? 0.0),
            'stock_quantity' => (int) $product->stock_quantity,
            'alert_quantity' => (int) ($product->alert_quantity ?? 10),
            'category_name' => (string) ($product->category?->name ?? 'Uncategorized'),
            'brand_name' => (string) ($product->brand?->name ?? 'Generic'),
            'is_active' => (bool) $product->is_active,
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->saveDocument('products', "product_{$product->id}", $payload);
    }

    /**
     * Store Category in Firebase Firestore.
     */
    public function storeCategory(Category $category): bool
    {
        $payload = [
            'id' => (int) $category->id,
            'name' => (string) $category->name,
            'slug' => (string) $category->slug,
            'description' => (string) ($category->description ?? ''),
            'is_active' => (bool) ($category->is_active ?? true),
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->saveDocument('categories', "cat_{$category->id}", $payload);
    }

    /**
     * Store Brand in Firebase Firestore.
     */
    public function storeBrand(Brand $brand): bool
    {
        $payload = [
            'id' => (int) $brand->id,
            'name' => (string) $brand->name,
            'slug' => (string) $brand->slug,
            'description' => (string) ($brand->description ?? ''),
            'is_active' => (bool) ($brand->is_active ?? true),
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->saveDocument('brands', "brand_{$brand->id}", $payload);
    }

    /**
     * Store Customer in Firebase Firestore.
     */
    public function storeCustomer(Customer $customer): bool
    {
        $payload = [
            'id' => (int) $customer->id,
            'name' => (string) $customer->name,
            'phone' => (string) ($customer->phone ?? ''),
            'email' => (string) ($customer->email ?? ''),
            'address' => (string) ($customer->address ?? ''),
            'total_spent' => (float) ($customer->total_spent ?? 0.0),
            'points' => (int) ($customer->points ?? 0),
            'balance' => (float) ($customer->balance ?? 0.0),
            'is_active' => (bool) ($customer->is_active ?? true),
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->saveDocument('customers', "customer_{$customer->id}", $payload);
    }

    /**
     * Store Supplier in Firebase Firestore.
     */
    public function storeSupplier(Supplier $supplier): bool
    {
        $payload = [
            'id' => (int) $supplier->id,
            'name' => (string) $supplier->name,
            'company_name' => (string) ($supplier->company_name ?? ''),
            'phone' => (string) ($supplier->phone ?? ''),
            'email' => (string) ($supplier->email ?? ''),
            'address' => (string) ($supplier->address ?? ''),
            'tax_number' => (string) ($supplier->tax_number ?? ''),
            'is_active' => (bool) ($supplier->is_active ?? true),
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->saveDocument('suppliers', "supplier_{$supplier->id}", $payload);
    }

    /**
     * Store Purchase Order in Firebase Firestore.
     */
    public function storePurchase(Purchase $purchase): bool
    {
        $purchase->loadMissing(['supplier', 'items.product']);

        $items = [];
        if ($purchase->relationLoaded('items')) {
            foreach ($purchase->items as $item) {
                $items[] = [
                    'product_id' => (int) $item->product_id,
                    'name' => (string) ($item->product->name ?? 'Product #' . $item->product_id),
                    'quantity' => (int) $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                    'subtotal' => (float) $item->subtotal,
                ];
            }
        }

        $payload = [
            'id' => (int) $purchase->id,
            'purchase_no' => (string) ($purchase->purchase_no ?? 'PO-' . $purchase->id),
            'supplier_name' => (string) ($purchase->supplier?->name ?? 'Unknown Supplier'),
            'purchase_date' => $purchase->purchase_date ? $purchase->purchase_date->toIso8601String() : now()->toIso8601String(),
            'total_amount' => (float) $purchase->total_amount,
            'status' => (string) ($purchase->status ?? 'received'),
            'items' => $items,
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->saveDocument('purchases', "purchase_{$purchase->id}", $payload);
    }

    /**
     * Store Expense in Firebase Firestore.
     */
    public function storeExpense(Expense $expense): bool
    {
        $expense->loadMissing(['category', 'user']);

        $payload = [
            'id' => (int) $expense->id,
            'title' => (string) ($expense->title ?? $expense->name ?? 'Business Expense'),
            'category_name' => (string) ($expense->category?->name ?? 'General'),
            'amount' => (float) $expense->amount,
            'expense_date' => $expense->expense_date ? $expense->expense_date->toIso8601String() : now()->toIso8601String(),
            'created_by' => (string) ($expense->user?->name ?? 'Staff'),
            'notes' => (string) ($expense->notes ?? $expense->description ?? ''),
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->saveDocument('expenses', "expense_{$expense->id}", $payload);
    }

    /**
     * Store Return Order in Firebase Firestore.
     */
    public function storeReturn(ReturnOrder $return): bool
    {
        $return->loadMissing(['sale', 'customer', 'items.product']);

        $items = [];
        if ($return->relationLoaded('items')) {
            foreach ($return->items as $item) {
                $items[] = [
                    'product_id' => (int) $item->product_id,
                    'name' => (string) ($item->product->name ?? 'Item'),
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) ($item->unit_price ?? 0.0),
                    'subtotal' => (float) ($item->subtotal ?? 0.0),
                ];
            }
        }

        $payload = [
            'id' => (int) $return->id,
            'return_no' => (string) ($return->return_no ?? 'RET-' . $return->id),
            'invoice_no' => (string) ($return->sale?->invoice_no ?? ''),
            'customer_name' => (string) ($return->customer?->name ?? 'Walk-in Customer'),
            'refund_amount' => (float) ($return->refund_amount ?? $return->total_amount ?? 0.0),
            'reason' => (string) ($return->reason ?? ''),
            'items' => $items,
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->saveDocument('returns', "return_{$return->id}", $payload);
    }

    /**
     * Store Stock Movement in Firebase Firestore.
     */
    public function storeStockMovement(StockMovement $movement): bool
    {
        $movement->loadMissing(['product', 'user']);

        $payload = [
            'id' => (int) $movement->id,
            'product_id' => (int) $movement->product_id,
            'product_name' => (string) ($movement->product?->name ?? 'Unknown'),
            'type' => (string) $movement->type,
            'quantity' => (int) $movement->quantity,
            'reference_type' => (string) ($movement->reference_type ?? ''),
            'user_name' => (string) ($movement->user?->name ?? 'System'),
            'notes' => (string) ($movement->notes ?? ''),
            'created_at' => $movement->created_at ? $movement->created_at->toIso8601String() : now()->toIso8601String(),
        ];

        return $this->saveDocument('stock_movements', "movement_{$movement->id}", $payload);
    }

    /**
     * Store Application Setting in Firebase Firestore.
     */
    public function storeSetting(Setting $setting): bool
    {
        $payload = [
            'key' => (string) $setting->key,
            'value' => (string) ($setting->value ?? ''),
            'updated_at' => now()->toIso8601String(),
        ];

        $key = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $setting->key);
        return $this->saveDocument('settings', "setting_{$key}", $payload);
    }

    /**
     * Store User Account in Firebase Firestore.
     */
    public function storeUser(User $user): bool
    {
        $user->loadMissing('roles');

        $payload = [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'is_active' => (bool) $user->is_active,
            'roles' => $user->roles->pluck('name')->toArray(),
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->saveDocument('users', "user_{$user->id}", $payload);
    }

    /**
     * Store System Notification in Firebase Firestore.
     */
    public function storeNotification(Notification $notification): bool
    {
        $payload = [
            'id' => (int) $notification->id,
            'type' => (string) $notification->type,
            'title' => (string) $notification->title,
            'message' => (string) $notification->message,
            'is_read' => (bool) $notification->is_read,
            'created_at' => $notification->created_at ? $notification->created_at->toIso8601String() : now()->toIso8601String(),
        ];

        return $this->saveDocument('notifications', "notif_{$notification->id}", $payload);
    }

    /**
     * Store Activity Log in Firebase Firestore.
     */
    public function storeActivityLog(ActivityLog $log): bool
    {
        $payload = [
            'id' => (int) $log->id,
            'user_name' => (string) ($log->user?->name ?? 'System'),
            'action' => (string) $log->action,
            'module' => (string) ($log->module ?? 'General'),
            'description' => (string) $log->description,
            'ip_address' => (string) ($log->ip_address ?? '127.0.0.1'),
            'created_at' => $log->created_at ? $log->created_at->toIso8601String() : now()->toIso8601String(),
        ];

        return $this->saveDocument('activity_logs', "log_{$log->id}", $payload);
    }

    /**
     * Sync full dataset from database to Firebase Firestore.
     */
    public function syncAll(): array
    {
        $counts = [
            'products' => 0,
            'sales' => 0,
            'customers' => 0,
            'suppliers' => 0,
            'categories' => 0,
            'brands' => 0,
            'purchases' => 0,
            'expenses' => 0,
            'returns' => 0,
            'settings' => 0,
            'users' => 0,
        ];

        // 1. Sync Products
        Product::with(['category', 'brand'])->chunk(50, function ($products) use (&$counts) {
            foreach ($products as $product) {
                if ($this->storeProduct($product)) {
                    $counts['products']++;
                }
            }
        });

        // 2. Sync Sales
        Sale::with(['items.product', 'customer', 'user'])->chunk(50, function ($sales) use (&$counts) {
            foreach ($sales as $sale) {
                if ($this->storeSale($sale)) {
                    $counts['sales']++;
                }
            }
        });

        // 3. Sync Customers
        Customer::chunk(50, function ($customers) use (&$counts) {
            foreach ($customers as $customer) {
                if ($this->storeCustomer($customer)) {
                    $counts['customers']++;
                }
            }
        });

        // 4. Sync Suppliers
        Supplier::chunk(50, function ($suppliers) use (&$counts) {
            foreach ($suppliers as $supplier) {
                if ($this->storeSupplier($supplier)) {
                    $counts['suppliers']++;
                }
            }
        });

        // 5. Sync Categories
        Category::all()->each(function ($cat) use (&$counts) {
            if ($this->storeCategory($cat)) {
                $counts['categories']++;
            }
        });

        // 6. Sync Brands
        Brand::all()->each(function ($brand) use (&$counts) {
            if ($this->storeBrand($brand)) {
                $counts['brands']++;
            }
        });

        // 7. Sync Purchases
        Purchase::with(['supplier', 'items.product'])->chunk(50, function ($purchases) use (&$counts) {
            foreach ($purchases as $purchase) {
                if ($this->storePurchase($purchase)) {
                    $counts['purchases']++;
                }
            }
        });

        // 8. Sync Expenses
        Expense::with(['category', 'user'])->chunk(50, function ($expenses) use (&$counts) {
            foreach ($expenses as $expense) {
                if ($this->storeExpense($expense)) {
                    $counts['expenses']++;
                }
            }
        });

        // 9. Sync Returns
        ReturnOrder::with(['sale', 'customer', 'items.product'])->chunk(50, function ($returns) use (&$counts) {
            foreach ($returns as $return) {
                if ($this->storeReturn($return)) {
                    $counts['returns']++;
                }
            }
        });

        // 10. Sync Settings
        Setting::all()->each(function ($setting) use (&$counts) {
            if ($this->storeSetting($setting)) {
                $counts['settings']++;
            }
        });

        // 11. Sync Users
        User::with('roles')->get()->each(function ($user) use (&$counts) {
            if ($this->storeUser($user)) {
                $counts['users']++;
            }
        });

        return $counts;
    }

    /**
     * Retrieve complete snapshot payload for client-side Firebase batch storing.
     */
    public function getExportPayload(): array
    {
        $products = Product::with(['category:id,name', 'brand:id,name'])
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'barcode' => $p->barcode,
                'selling_price' => (float) $p->selling_price,
                'purchase_price' => (float) ($p->purchase_price ?? $p->cost_price ?? 0.0),
                'stock_quantity' => (int) $p->stock_quantity,
                'category' => $p->category?->name ?? 'General',
                'brand' => $p->brand?->name ?? 'Generic',
                'is_active' => (bool) $p->is_active,
            ]);

        $sales = Sale::with(['items.product:id,name,sku', 'customer:id,name,phone', 'user:id,name'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'invoice_no' => $s->invoice_no,
                'sale_date' => $s->sale_date?->toIso8601String(),
                'total_amount' => (float) $s->total_amount,
                'paid_amount' => (float) $s->paid_amount,
                'payment_method' => $s->payment_method,
                'payment_status' => $s->payment_status,
                'customer_name' => $s->customer?->name ?? 'Walk-in Customer',
                'cashier_name' => $s->user?->name ?? 'Cashier',
                'items_count' => $s->items->count(),
            ]);

        $customers = Customer::latest('id')
            ->limit(50)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'email' => $c->email,
                'total_spent' => (float) $c->total_spent,
                'points' => (int) $c->points,
            ]);

        return [
            'project_id' => $this->projectId,
            'timestamp' => now()->toIso8601String(),
            'products' => $products,
            'sales' => $sales,
            'customers' => $customers,
        ];
    }

    /**
     * Helper to convert standard PHP associative array to Firestore typed format.
     */
    public function toFirestoreFields(array $data): array
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[$key] = $this->toFirestoreValue($value);
        }
        return $fields;
    }

    /**
     * Convert PHP value to Firestore typed object.
     */
    protected function toFirestoreValue(mixed $value): array
    {
        if (is_null($value)) {
            return ['nullValue' => null];
        }

        if (is_bool($value)) {
            return ['booleanValue' => $value];
        }

        if (is_int($value)) {
            return ['integerValue' => (string) $value];
        }

        if (is_float($value)) {
            return ['doubleValue' => $value];
        }

        if (is_array($value)) {
            // Check if associative
            if (array_keys($value) !== range(0, count($value) - 1)) {
                return [
                    'mapValue' => [
                        'fields' => $this->toFirestoreFields($value),
                    ],
                ];
            }

            // Indexed list
            $values = [];
            foreach ($value as $item) {
                $values[] = $this->toFirestoreValue($item);
            }
            return [
                'arrayValue' => [
                    'values' => $values,
                ],
            ];
        }

        return ['stringValue' => (string) $value];
    }
}
