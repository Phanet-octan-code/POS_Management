<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\FirebaseService;
use App\Services\PosService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FirebaseIntegrationTest extends TestCase
{
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['firebase.enabled' => true]);

        Http::fake([
            'https://firestore.googleapis.com/*' => Http::response(['name' => 'projects/pos-management-88866/databases/(default)/documents/test_doc'], 200),
        ]);

        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@pos.com')->first();
    }

    public function test_firebase_configuration_is_loaded(): void
    {
        $this->assertEquals('pos-management-88866', config('firebase.project_id'));
        $this->assertEquals('AIzaSyBc5n8S7mzPA99K6TmuKVT7n7whLRYCFKg', config('firebase.api_key'));
        $this->assertEquals('pos-management-88866.firebaseapp.com', config('firebase.auth_domain'));
        $this->assertEquals('pos-management-88866.firebasestorage.app', config('firebase.storage_bucket'));
        $this->assertEquals('1089593768258', config('firebase.messaging_sender_id'));
        $this->assertEquals('1:1089593768258:web:7a6428fcb624a812e530db', config('firebase.app_id'));
        $this->assertEquals('G-Q021Y54GX7', config('firebase.measurement_id'));
    }

    public function test_firebase_service_formats_firestore_fields_accurately(): void
    {
        $service = app(FirebaseService::class);
        $data = [
            'string_val' => 'OmniPOS',
            'int_val' => 42,
            'float_val' => 19.99,
            'bool_val' => true,
            'null_val' => null,
            'array_val' => ['item1', 'item2'],
            'map_val' => ['nested' => 'value'],
        ];

        $fields = $service->toFirestoreFields($data);

        $this->assertEquals(['stringValue' => 'OmniPOS'], $fields['string_val']);
        $this->assertEquals(['integerValue' => '42'], $fields['int_val']);
        $this->assertEquals(['doubleValue' => 19.99], $fields['float_val']);
        $this->assertEquals(['booleanValue' => true], $fields['bool_val']);
        $this->assertEquals(['nullValue' => null], $fields['null_val']);
        $this->assertArrayHasKey('arrayValue', $fields['array_val']);
        $this->assertArrayHasKey('mapValue', $fields['map_val']);
    }

    public function test_firebase_service_stores_sale_document(): void
    {
        Http::fake([
            'https://firestore.googleapis.com/*' => Http::response(['name' => 'projects/pos-management-88866/databases/(default)/documents/sales/sale_1'], 200),
        ]);

        $service = app(FirebaseService::class);
        $posService = app(PosService::class);
        $product = Product::where('stock_quantity', '>=', 5)->first();
        if (!$product) {
            $product = Product::first();
            $product->update(['stock_quantity' => 25]);
        }

        $this->actingAs($this->admin);
        $sale = $posService->processSale([
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => $product->selling_price,
                    'discount' => 0.0,
                    'subtotal' => $product->selling_price,
                ],
            ],
            'subtotal' => $product->selling_price,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $product->selling_price,
            'paid_amount' => $product->selling_price,
            'payment_method' => 'cash',
        ]);

        $this->assertNotNull($sale);
        $result = $service->storeSale($sale);

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'firestore.googleapis.com') &&
                   str_contains($request->url(), 'pos-management-88866');
        });
    }

    public function test_firebase_service_stores_product_document(): void
    {
        Http::fake([
            'https://firestore.googleapis.com/*' => Http::response(['name' => 'projects/pos-management-88866/databases/(default)/documents/products/product_1'], 200),
        ]);

        $service = app(FirebaseService::class);
        $product = Product::first();

        $this->assertNotNull($product, 'Product record must exist from seeder.');
        $result = $service->storeProduct($product);

        $this->assertTrue($result);
    }

    public function test_firebase_status_endpoint_returns_json(): void
    {
        Http::fake([
            'https://firestore.googleapis.com/*' => Http::response(['status' => 'connected'], 200),
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('firebase.status'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'project' => 'pos-management-88866',
            ]);
    }

    public function test_firebase_export_payload_returns_dataset_for_client_sync(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('firebase.export-payload'));

        $response->assertOk()
            ->assertJsonStructure([
                'project_id',
                'timestamp',
                'products',
                'sales',
                'customers',
            ]);
        $this->assertEquals('pos-management-88866', $response->json('project_id'));
    }

    public function test_firebase_sync_command_runs_successfully(): void
    {
        Http::fake([
            'https://firestore.googleapis.com/*' => Http::response(['name' => 'doc'], 200),
        ]);

        $this->artisan('firebase:sync')
            ->expectsOutputToContain('pos-management-88866')
            ->assertExitCode(0);
    }

    public function test_pos_checkout_triggers_firebase_storage(): void
    {
        Http::fake([
            'https://firestore.googleapis.com/*' => Http::response(['name' => 'doc'], 200),
        ]);

        $product = Product::where('stock_quantity', '>=', 5)->first();
        if (!$product) {
            $product = Product::first();
            $product->update(['stock_quantity' => 25]);
        }

        $customer = \App\Models\Customer::first();

        $payload = [
            'customer_id' => $customer?->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => $product->selling_price,
                    'discount' => 0.0,
                    'subtotal' => $product->selling_price,
                ],
            ],
            'subtotal' => $product->selling_price,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => $product->selling_price,
            'paid_amount' => $product->selling_price,
            'payment_method' => 'cash',
        ];

        $response = $this->actingAs($this->admin)->postJson(route('pos.checkout'), $payload);

        $response->assertOk()
            ->assertJson(['success' => true]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'firestore.googleapis.com');
        });
    }

    public function test_model_creation_triggers_firebase_sync_observer(): void
    {
        Http::fake([
            'https://firestore.googleapis.com/*' => Http::response(['name' => 'doc'], 200),
        ]);

        $uniq = uniqid();
        \App\Models\Category::create([
            'name' => "Automated Test Category {$uniq}",
            'slug' => "automated-test-category-{$uniq}",
            'description' => 'Testing observer auto-sync',
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'firestore.googleapis.com') &&
                   str_contains($request->url(), 'categories');
        });
    }

    public function test_firebase_service_stores_category_and_supplier(): void
    {
        Http::fake([
            'https://firestore.googleapis.com/*' => Http::response(['name' => 'doc'], 200),
        ]);

        $service = app(FirebaseService::class);
        $category = \App\Models\Category::first();
        $supplier = \App\Models\Supplier::first();

        $this->assertTrue($service->storeCategory($category));
        $this->assertTrue($service->storeSupplier($supplier));
    }
}
