<?php

namespace App\Providers;

use App\Models\Product;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Implicitly grant 'Admin' role all permissions
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if ($user->isAdmin()) {
                return true;
            }
        });

        // Register Gates for the 14 Standard Permissions
        $permissions = [
            'dashboard', 'pos', 'products', 'categories', 'brands',
            'customers', 'suppliers', 'purchases', 'sales', 'inventory',
            'expenses', 'reports', 'users', 'settings'
        ];

        foreach ($permissions as $perm) {
            \Illuminate\Support\Facades\Gate::define($perm, function ($user) use ($perm) {
                return $user->hasPermission($perm);
            });
        }

        // Globally share dynamic application settings with all views
        View::composer('*', function ($view) {
            $view->with('appSettings', \App\Models\Setting::getAll());
        });

        View::composer('*', function ($view) {
            $unreadCount = 0;
            $navbarNotifications = collect();
            $navbarStockAlerts = collect();

            if (Schema::hasTable('notifications')) {
                $notificationService = app(\App\Services\NotificationService::class);
                $unreadCount = $notificationService->getUnreadCount();
                $navbarNotifications = $notificationService->getNavbarNotifications(6);
            }

            if (Schema::hasTable('products')) {
                $navbarStockAlerts = Product::where('is_active', true)
                    ->whereColumn('stock_quantity', '<=', 'alert_quantity')
                    ->take(5)
                    ->get();
            }

            $view->with('navbarAlertCount', $unreadCount)
                 ->with('navbarUnreadCount', $unreadCount)
                 ->with('navbarNotifications', $navbarNotifications)
                 ->with('navbarStockAlerts', $navbarStockAlerts);
        });
    }
}
