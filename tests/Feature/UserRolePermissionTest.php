<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserRolePermissionTest extends TestCase
{
    protected User $admin;
    protected User $manager;
    protected User $cashier;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('email', 'admin@pos.com')->first() ?? User::factory()->create([
            'email' => 'admin@pos.com',
            'is_active' => true,
        ]);
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole && !$this->admin->roles()->where('slug', 'admin')->exists()) {
            $this->admin->roles()->sync([$adminRole->id]);
        }

        $this->manager = User::where('email', 'manager@pos.com')->first();
        $this->cashier = User::where('email', 'cashier@pos.com')->first();
        $this->staff = User::where('email', 'staff@pos.com')->first();
    }

    /**
     * 1. Verify all 14 canonical permissions exist.
     */
    public function test_14_canonical_permissions_exist(): void
    {
        $expectedPermissions = [
            'dashboard', 'pos', 'products', 'categories', 'brands',
            'customers', 'suppliers', 'purchases', 'sales', 'inventory',
            'expenses', 'reports', 'users', 'settings'
        ];

        foreach ($expectedPermissions as $slug) {
            $this->assertDatabaseHas('permissions', ['slug' => $slug]);
        }

        $this->assertEquals(14, Permission::whereIn('slug', $expectedPermissions)->count());
    }

    /**
     * 2. Verify all 4 roles exist: Admin, Manager, Cashier, Staff.
     */
    public function test_4_core_roles_exist(): void
    {
        $expectedRoles = ['admin', 'manager', 'cashier', 'staff'];

        foreach ($expectedRoles as $slug) {
            $this->assertDatabaseHas('roles', ['slug' => $slug]);
        }
    }

    /**
     * 3. Test User CRUD: Add, Edit, Delete, View, Search.
     */
    public function test_user_management_crud_and_search(): void
    {
        $cashierRole = Role::where('slug', 'cashier')->first();

        // 3a. Add User
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Test Cashier User',
            'email' => 'test_cashier_' . uniqid() . '@pos.com',
            'phone' => '+1 555-987-6543',
            'password' => 'secret1234',
            'roles' => [$cashierRole->id],
            'is_active' => '1',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $createdUser = User::where('name', 'Test Cashier User')->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue($createdUser->is_active);
        $this->assertTrue($createdUser->roles()->where('slug', 'cashier')->exists());

        // 3b. View User (JSON response)
        $viewResponse = $this->actingAs($this->admin)->getJson(route('users.show', $createdUser));
        $viewResponse->assertOk();
        $viewResponse->assertJsonPath('success', true);
        $viewResponse->assertJsonPath('user.name', 'Test Cashier User');
        $viewResponse->assertJsonPath('user.roles.0.slug', 'cashier');

        // 3c. Search User
        $searchResponse = $this->actingAs($this->admin)->get(route('users.index', ['q' => 'Test Cashier User']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Test Cashier User');

        // 3d. Edit User
        $updateResponse = $this->actingAs($this->admin)->put(route('users.update', $createdUser), [
            'name' => 'Updated Cashier Name',
            'email' => $createdUser->email,
            'phone' => '+1 555-111-2222',
            'roles' => [$cashierRole->id],
            'is_active' => '1',
        ]);
        $updateResponse->assertRedirect();
        $this->assertEquals('Updated Cashier Name', $createdUser->fresh()->name);
        $this->assertEquals('+1 555-111-2222', $createdUser->fresh()->phone);

        // 3e. Delete User
        $deleteResponse = $this->actingAs($this->admin)->delete(route('users.destroy', $createdUser));
        $deleteResponse->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $createdUser->id]);

        // 3f. Primary Admin (id 1) cannot be deleted
        $primaryAdmin = User::find(1);
        if ($primaryAdmin) {
            $delAdminResponse = $this->actingAs($this->admin)->delete(route('users.destroy', $primaryAdmin));
            $delAdminResponse->assertSessionHasErrors('error');
            $this->assertDatabaseHas('users', ['id' => 1, 'deleted_at' => null]);
        }
    }

    /**
     * 4. Test Activate and Deactivate user.
     */
    public function test_user_activation_and_deactivation(): void
    {
        $testUser = User::create([
            'name' => 'Status Toggle User',
            'email' => 'toggle_' . uniqid() . '@pos.com',
            'password' => Hash::make('secret1234'),
            'is_active' => true,
        ]);

        // Deactivate
        $deactResponse = $this->actingAs($this->admin)->patchJson(route('users.toggle-status', $testUser));
        $deactResponse->assertOk();
        $deactResponse->assertJsonPath('success', true);
        $deactResponse->assertJsonPath('is_active', false);
        $this->assertFalse($testUser->fresh()->is_active);

        // Reactivate
        $actResponse = $this->actingAs($this->admin)->patchJson(route('users.toggle-status', $testUser));
        $actResponse->assertOk();
        $actResponse->assertJsonPath('success', true);
        $actResponse->assertJsonPath('is_active', true);
        $this->assertTrue($testUser->fresh()->is_active);

        // Clean up
        $testUser->forceDelete();
    }

    /**
     * 5. Test Reset Password.
     */
    public function test_user_reset_password(): void
    {
        $testUser = User::create([
            'name' => 'Password Reset User',
            'email' => 'reset_' . uniqid() . '@pos.com',
            'password' => Hash::make('old_password_123'),
            'is_active' => true,
        ]);

        $resetResponse = $this->actingAs($this->admin)->post(route('users.reset-password', $testUser), [
            'password' => 'new_brand_secret_999',
            'password_confirmation' => 'new_brand_secret_999',
        ]);
        $resetResponse->assertRedirect();
        $resetResponse->assertSessionHas('success');

        // Verify password changed
        $this->assertTrue(Hash::check('new_brand_secret_999', $testUser->fresh()->password));

        $testUser->forceDelete();
    }

    /**
     * 6. Test Deactivated User is blocked by CheckActiveUserMiddleware.
     */
    public function test_deactivated_user_is_blocked_by_middleware(): void
    {
        $deactivatedUser = User::create([
            'name' => 'Blocked Inactive User',
            'email' => 'inactive_' . uniqid() . '@pos.com',
            'password' => Hash::make('password'),
            'is_active' => false,
        ]);

        $response = $this->actingAs($deactivatedUser)->get(route('dashboard'));
        // Inactive user should be logged out and redirected to login
        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $deactivatedUser->forceDelete();
    }

    /**
     * 7. Test Admin can assign and revoke permissions to/from roles.
     */
    public function test_admin_can_assign_permissions_to_roles(): void
    {
        $cashierRole = Role::where('slug', 'cashier')->first();
        $reportsPerm = Permission::where('slug', 'reports')->first();

        $this->assertNotNull($cashierRole);
        $this->assertNotNull($reportsPerm);

        // Initially cashier does not have reports permission
        $initialPermIds = $cashierRole->permissions->pluck('id')->all();
        $this->assertNotContains($reportsPerm->id, $initialPermIds);

        // Admin assigns reports permission to cashier role
        $updatedPermIds = array_unique(array_merge($initialPermIds, [$reportsPerm->id]));
        $assignResponse = $this->actingAs($this->admin)->put(route('roles.update', $cashierRole), [
            'name' => $cashierRole->name,
            'description' => $cashierRole->description,
            'permissions' => $updatedPermIds,
        ]);
        $assignResponse->assertRedirect();

        $this->assertTrue($cashierRole->fresh()->permissions()->where('slug', 'reports')->exists());
        $this->assertTrue($this->cashier->fresh()->hasPermission('reports'));

        // Admin revokes reports permission from cashier role
        $revokedPermIds = array_diff($updatedPermIds, [$reportsPerm->id]);
        $this->actingAs($this->admin)->put(route('roles.update', $cashierRole), [
            'name' => $cashierRole->name,
            'description' => $cashierRole->description,
            'permissions' => $revokedPermIds,
        ]);

        $this->assertFalse($cashierRole->fresh()->permissions()->where('slug', 'reports')->exists());
        $this->assertFalse($this->cashier->fresh()->hasPermission('reports'));
    }

    /**
     * 8. Test Role: ADMIN has full unrestricted access to all 14 modules.
     */
    public function test_admin_role_has_full_access(): void
    {
        $routes = [
            'dashboard' => '/dashboard',
            'pos' => '/pos',
            'products' => '/products',
            'categories' => '/categories',
            'brands' => '/brands',
            'inventory' => '/inventory',
            'customers' => '/customers',
            'suppliers' => '/suppliers',
            'purchases' => '/purchases',
            'sales' => '/sales',
            'expenses' => '/expenses',
            'reports' => '/reports',
            'users' => '/users',
            'roles' => '/roles',
            'settings' => '/settings',
        ];

        foreach ($routes as $mod => $uri) {
            $response = $this->actingAs($this->admin)->get($uri);
            $this->assertNotEquals(403, $response->status(), "Admin should not receive 403 on {$uri} (module {$mod})");
            $this->assertTrue(in_array($response->status(), [200, 302]), "Admin should access {$uri}");
        }

        $rolesResponse = $this->actingAs($this->admin)->get('/roles');
        $rolesResponse->assertOk();
        $rolesResponse->assertSee('Roles & Permissions');
        $rolesResponse->assertSee('Assign Permissions');
    }

    /**
     * 9. Test Role: MANAGER permitted on store operations, denied on users & settings.
     */
    public function test_manager_role_access_control(): void
    {
        $this->assertNotNull($this->manager);

        // Permitted routes
        $permitted = ['/dashboard', '/pos', '/products', '/categories', '/brands', '/inventory', '/customers', '/suppliers', '/purchases', '/sales', '/expenses', '/reports'];
        foreach ($permitted as $uri) {
            $res = $this->actingAs($this->manager)->get($uri);
            $this->assertNotEquals(403, $res->status(), "Manager should have access to {$uri}");
        }

        // Denied routes (Users & Settings)
        $denied = ['/users', '/settings'];
        foreach ($denied as $uri) {
            $res = $this->actingAs($this->manager)->get($uri);
            $this->assertEquals(403, $res->status(), "Manager should receive 403 on {$uri}");
        }
    }

    /**
     * 10. Test Role: CASHIER permitted on POS, Sales, Customers; denied on others.
     */
    public function test_cashier_role_access_control(): void
    {
        $this->assertNotNull($this->cashier);

        // Permitted routes
        $permitted = ['/pos', '/sales', '/customers'];
        foreach ($permitted as $uri) {
            $res = $this->actingAs($this->cashier)->get($uri);
            $this->assertNotEquals(403, $res->status(), "Cashier should have access to {$uri}");
        }

        // Denied routes
        $denied = ['/dashboard', '/products', '/categories', '/brands', '/inventory', '/suppliers', '/purchases', '/expenses', '/reports', '/users', '/settings'];
        foreach ($denied as $uri) {
            $res = $this->actingAs($this->cashier)->get($uri);
            $this->assertEquals(403, $res->status(), "Cashier should receive 403 on {$uri}");
        }
    }

    /**
     * 11. Test Role: STAFF permitted on Products, Categories, Brands, Inventory, Customers; denied on others.
     */
    public function test_staff_role_access_control(): void
    {
        $this->assertNotNull($this->staff);

        // Permitted routes
        $permitted = ['/products', '/categories', '/brands', '/inventory', '/customers'];
        foreach ($permitted as $uri) {
            $res = $this->actingAs($this->staff)->get($uri);
            $this->assertNotEquals(403, $res->status(), "Staff should have access to {$uri}");
        }

        // Denied routes
        $denied = ['/dashboard', '/pos', '/sales', '/suppliers', '/purchases', '/expenses', '/reports', '/users', '/settings'];
        foreach ($denied as $uri) {
            $res = $this->actingAs($this->staff)->get($uri);
            $this->assertEquals(403, $res->status(), "Staff should receive 403 on {$uri}");
        }
    }

    /**
     * 12. Test Dynamic Permission Enforcement:
     * Revoking POS from Cashier immediately returns 403; granting it back restores 200.
     */
    public function test_dynamic_permission_enforcement(): void
    {
        $cashierRole = Role::where('slug', 'cashier')->first();
        $posPerm = Permission::where('slug', 'pos')->first();

        // 1. Initially Cashier has POS -> 200 OK
        $res1 = $this->actingAs($this->cashier)->get('/pos');
        $this->assertEquals(200, $res1->status());

        // 2. Revoke POS permission from Cashier
        $cashierRole->permissions()->detach($posPerm->id);
        $this->assertFalse($this->cashier->fresh()->hasPermission('pos'));

        // 3. Cashier visits /pos -> 403 Forbidden!
        $res2 = $this->actingAs($this->cashier)->get('/pos');
        $this->assertEquals(403, $res2->status());

        // 4. Restore POS permission to Cashier
        $cashierRole->permissions()->attach($posPerm->id);
        $this->assertTrue($this->cashier->fresh()->hasPermission('pos'));

        // 5. Cashier visits /pos -> 200 OK again!
        $res3 = $this->actingAs($this->cashier)->get('/pos');
        $this->assertEquals(200, $res3->status());
    }
}
