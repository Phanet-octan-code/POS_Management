<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AuthAndRoleTest extends TestCase
{
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $responsePos = $this->get('/pos');
        $responsePos->assertRedirect('/login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@pos.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@pos.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $admin = User::where('email', 'admin@pos.com')->first();

        $response = $this->actingAs($admin)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_cashier_can_access_pos_but_cannot_access_dashboard_or_settings(): void
    {
        $cashier = User::where('email', 'cashier@pos.com')->first();

        // Access POS -> Allowed (200)
        $this->actingAs($cashier)->get('/pos')->assertStatus(200);

        // Access Sales -> Allowed (200)
        $this->actingAs($cashier)->get('/sales')->assertStatus(200);

        // Access Customers -> Allowed (200)
        $this->actingAs($cashier)->get('/customers')->assertStatus(200);

        // Access Dashboard -> Restricted (403)
        $this->actingAs($cashier)->get('/dashboard')->assertStatus(403);

        // Access Settings -> Restricted (403)
        $this->actingAs($cashier)->get('/settings')->assertStatus(403);

        // Access Purchases -> Restricted (403)
        $this->actingAs($cashier)->get('/purchases')->assertStatus(403);
    }

    public function test_staff_can_access_catalog_but_cannot_access_pos_or_reports(): void
    {
        $staff = User::where('email', 'staff@pos.com')->first();

        // Access Products -> Allowed (200)
        $this->actingAs($staff)->get('/products')->assertStatus(200);

        // Access Inventory -> Allowed (200)
        $this->actingAs($staff)->get('/inventory')->assertStatus(200);

        // Access POS -> Restricted (403)
        $this->actingAs($staff)->get('/pos')->assertStatus(403);

        // Access Reports -> Restricted (403)
        $this->actingAs($staff)->get('/reports')->assertStatus(403);
    }

    public function test_manager_can_access_dashboard_and_reports_but_not_admin_settings(): void
    {
        $manager = User::where('email', 'manager@pos.com')->first();

        // Access Dashboard -> Allowed (200)
        $this->actingAs($manager)->get('/dashboard')->assertStatus(200);

        // Access Reports -> Allowed (302 redirect to sales report, or 200 on /reports/sales)
        $this->actingAs($manager)->get('/reports/sales')->assertStatus(200);

        // Access Purchases -> Allowed (200)
        $this->actingAs($manager)->get('/purchases')->assertStatus(200);

        // Access Settings -> Restricted (403)
        $this->actingAs($manager)->get('/settings')->assertStatus(403);

        // Access Users Management -> Restricted (403)
        $this->actingAs($manager)->get('/users')->assertStatus(403);
    }

    public function test_admin_has_full_access(): void
    {
        $admin = User::where('email', 'admin@pos.com')->first();

        $this->actingAs($admin)->get('/dashboard')->assertStatus(200);
        $this->actingAs($admin)->get('/pos')->assertStatus(200);
        $this->actingAs($admin)->get('/products')->assertStatus(200);
        $this->actingAs($admin)->get('/settings')->assertStatus(200);
        $this->actingAs($admin)->get('/users')->assertStatus(200);
    }

    public function test_dashboard_ui_structure_and_sidebar(): void
    {
        $admin = User::where('email', 'admin@pos.com')->first();

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Poppins', false);
        $response->assertSee('OmniPOS', false);
        $response->assertSee('sidebarToggleBtn', false);
        $response->assertSee('notificationDropdown', false);
        $response->assertSee('userMenuBtn', false);
        $response->assertSee('salesTrendChart', false);
    }
}
