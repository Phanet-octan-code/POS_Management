<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Product;
use App\Models\Sale;
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
            'purchase_price' => (float) ($product->purchase_price ?? 0.0),
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
     * Store Customer in Firebase Firestore.
     */
    public function storeCustomer(Customer $customer): bool
    {
        $payload = [
            'id' => (int) $customer->id,
            'name' => (string) $customer->name,
            'phone' => (string) ($customer->phone ?? ''),
            'email' => (string) ($customer->email ?? ''),
            'total_spent' => (float) ($customer->total_spent ?? 0.0),
            'points' => (int) ($customer->points ?? 0),
            'balance' => (float) ($customer->balance ?? 0.0),
            'updated_at' => now()->toIso8601String(),
        ];

        return $this->saveDocument('customers', "customer_{$customer->id}", $payload);
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
            'module' => (string) $log->module,
            'description' => (string) $log->description,
            'ip_address' => (string) ($log->ip_address ?? '127.0.0.1'),
            'created_at' => $log->created_at ? $log->created_at->toIso8601String() : now()->toIso8601String(),
        ];

        return $this->saveDocument('activity_logs', "log_{$log->id}", $payload);
    }

    /**
     * Sync full dataset from MySQL to Firebase Firestore.
     */
    public function syncAll(): array
    {
        $counts = [
            'products' => 0,
            'sales' => 0,
            'customers' => 0,
            'categories' => 0,
            'activity_logs' => 0,
        ];

        // 1. Sync Products
        Product::with(['category', 'brand'])->chunk(50, function ($products) use (&$counts) {
            foreach ($products as $product) {
                if ($this->storeProduct($product)) {
                    $counts['products']++;
                }
            }
        });

        // 2. Sync Recent Sales
        Sale::with(['items.product', 'customer', 'user'])->latest('id')->limit(50)->get()->each(function ($sale) use (&$counts) {
            if ($this->storeSale($sale)) {
                $counts['sales']++;
            }
        });

        // 3. Sync Customers
        Customer::latest('id')->limit(50)->get()->each(function ($customer) use (&$counts) {
            if ($this->storeCustomer($customer)) {
                $counts['customers']++;
            }
        });

        // 4. Sync Categories
        Category::all()->each(function ($cat) use (&$counts) {
            $this->saveDocument('categories', "cat_{$cat->id}", [
                'id' => (int) $cat->id,
                'name' => (string) $cat->name,
                'slug' => (string) $cat->slug,
            ]);
            $counts['categories']++;
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
                'purchase_price' => (float) ($p->purchase_price ?? 0.0),
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
