<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\PosService;
use Tests\TestCase;

class SettingsModuleTest extends TestCase
{
    protected User $admin;
    protected User $cashier;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('email', 'admin@pos.com')->first();
        $this->cashier = User::where('email', 'cashier@pos.com')->first();
        $this->staff = User::where('email', 'staff@pos.com')->first();

        // Ensure clean cache before each test
        Setting::flushCache();
    }

    /**
     * 1. Admin can access settings page.
     */
    public function test_admin_can_view_settings_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('settings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('settings.index');
        $response->assertViewHas('settings');
        $response->assertSee('POS System Settings');
    }

    /**
     * 2. Staff without settings permission cannot access settings page (403).
     */
    public function test_unauthorized_user_cannot_access_settings(): void
    {
        $response = $this->actingAs($this->staff)->get(route('settings.index'));
        $response->assertStatus(403);

        $postResponse = $this->actingAs($this->staff)->post(route('settings.update'), [
            'settings' => ['store_name' => 'Hacked Store']
        ]);
        $postResponse->assertStatus(403);
    }

    /**
     * 3. Admin can update store settings and changes persist.
     */
    public function test_admin_can_update_store_settings(): void
    {
        $payload = [
            'settings' => [
                'store_name' => 'Phnom Penh Mega Store',
                'store_phone' => '+855 23 888 999',
                'store_email' => 'contact@megastore.kh',
                'store_website' => 'https://megastore.kh',
                'store_address' => 'No. 128 Preah Norodom Blvd, Phnom Penh',
                'currency_symbol' => '៛',
                'store_tax' => '10.00',
                'invoice_prefix' => 'MEGA-',
                'pos_default_tax' => '10.00',
                'pos_default_discount' => '0.00',
                'receipt_size' => '80mm',
                'enable_barcode' => '1',
                'enable_customer' => '1',
                'enable_sound' => '1',
            ]
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.update'), $payload);

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');

        // Verify cache and database reflection
        $this->assertEquals('Phnom Penh Mega Store', Setting::get('store_name'));
        $this->assertEquals('+855 23 888 999', Setting::get('store_phone'));
        $this->assertEquals('contact@megastore.kh', Setting::get('store_email'));
        $this->assertEquals('https://megastore.kh', Setting::get('store_website'));
        $this->assertEquals('៛', Setting::get('currency_symbol'));
        $this->assertEquals('MEGA-', Setting::get('invoice_prefix'));

        $this->assertDatabaseHas('settings', [
            'key' => 'store_name',
            'value' => 'Phnom Penh Mega Store',
        ]);
    }

    /**
     * 4. Unchecked switches are normalized to '0'.
     */
    public function test_unchecked_switches_are_normalized_to_zero(): void
    {
        $payload = [
            'settings' => [
                'store_name' => 'Minimal Boutique',
                'store_phone' => '+855 12 345 678',
                'store_email' => 'minimal@boutique.kh',
                'store_website' => 'https://minimal.kh',
                'store_address' => 'Street 240, Phnom Penh',
                'currency_symbol' => '$',
                'store_tax' => '5.00',
                'invoice_prefix' => 'MINI-',
                'pos_default_tax' => '5.00',
                'pos_default_discount' => '2.50',
                'receipt_size' => '58mm',
                // enable_barcode and enable_customer omitted (checkboxes unchecked in browser)
                'enable_sound' => '1',
            ]
        ];

        $response = $this->actingAs($this->admin)->post(route('settings.update'), $payload);

        $response->assertRedirect(route('settings.index'));
        $this->assertEquals('0', Setting::get('enable_barcode'));
        $this->assertEquals('0', Setting::get('enable_customer'));
        $this->assertEquals('1', Setting::get('enable_sound'));
        $this->assertEquals('58mm', Setting::get('receipt_size'));
        $this->assertEquals('5.00', Setting::get('pos_default_tax'));
    }

    /**
     * 5. Dynamic invoice prefix is used by PosService::generateInvoiceNumber().
     */
    public function test_pos_service_generates_invoice_with_dynamic_prefix(): void
    {
        Setting::set('invoice_prefix', 'SUPER-');

        $posService = app(PosService::class);
        $invoiceNumber = $posService->generateInvoiceNumber();

        $year = date('Y');
        $this->assertStringStartsWith("SUPER-{$year}-", $invoiceNumber);
        $this->assertMatchesRegularExpression('/^SUPER-\d{4}-\d{6}$/', $invoiceNumber);
    }

    /**
     * 6. POS terminal receives dynamic $posSettings in view.
     */
    public function test_pos_terminal_receives_dynamic_pos_settings(): void
    {
        Setting::setMany([
            'pos_default_tax' => '7.50',
            'pos_default_discount' => '3.00',
            'receipt_size' => 'a4',
            'currency_symbol' => '€',
            'enable_barcode' => '0',
            'enable_customer' => '1',
            'enable_sound' => '0',
        ]);

        $response = $this->actingAs($this->admin)->get(route('pos.index'));

        $response->assertStatus(200);
        $response->assertViewHas('posSettings');

        $posSettings = $response->viewData('posSettings');
        $this->assertEquals(7.50, $posSettings['default_tax']);
        $this->assertEquals(3.00, $posSettings['default_discount']);
        $this->assertEquals('a4', $posSettings['receipt_size']);
        $this->assertEquals('€', $posSettings['currency_symbol']);
        $this->assertFalse($posSettings['enable_barcode']);
        $this->assertTrue($posSettings['enable_customer']);
        $this->assertFalse($posSettings['enable_sound']);
    }

    /**
     * 7. Global appSettings is composed to all views.
     */
    public function test_global_app_settings_view_composer(): void
    {
        Setting::set('store_name', 'Global Store Test');

        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('appSettings');
        $appSettings = $response->viewData('appSettings');
        $this->assertEquals('Global Store Test', $appSettings['store_name']);
    }
}
