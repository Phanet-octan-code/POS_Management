<!-- Sidebar -->
<aside id="sidebar" class="pos-sidebar d-flex flex-column flex-shrink-0">
    <!-- Brand Header -->
    <div class="sidebar-header d-flex align-items-center justify-content-between px-3 py-3 border-bottom border-light-subtle">
        <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none w-100 overflow-hidden" style="gap: 0.85rem;">
            @php
                $storeLogo = $appSettings['store_logo'] ?? null;
                $hasCustomLogo = $storeLogo && $storeLogo !== 'images/store-logo.svg' && file_exists(public_path($storeLogo));
            @endphp
            @if($hasCustomLogo)
                <div class="sidebar-brand-badge flex-shrink-0">
                    <img src="{{ asset($storeLogo) }}" alt="{{ $appSettings['store_name'] ?? 'Store Logo' }}" style="width: 100%; height: 100%; object-fit: contain;">
                </div>
            @else
                <div class="sidebar-brand-badge flex-shrink-0 p-0 shadow-none border-0" style="background: transparent;">
                    <img src="{{ asset('images/store-icon.svg') }}" alt="{{ $appSettings['store_name'] ?? 'Store Logo' }}" style="width: 42px; height: 42px; border-radius: 10px;">
                </div>
            @endif
            <div class="d-flex flex-column overflow-hidden" style="min-width: 0; flex: 1;">
                <span class="sidebar-brand-title text-truncate" title="{{ $appSettings['store_name'] ?? 'OmniPOS' }}">
                    {{ $appSettings['store_name'] ?? 'OmniPOS' }}
                </span>
                <small class="text-white-50 text-truncate" style="font-size: 0.68rem; font-weight: 600; letter-spacing: 0.08em; text-transform: uppercase; margin-top: 2px;">
                    Retail &amp; Management
                </small>
            </div>
        </a>
        <button type="button" class="btn-close btn-close-white d-lg-none ms-2" id="sidebarCloseBtn" aria-label="Close"></button>
    </div>

    <!-- Navigation Menu Items -->
    <div class="sidebar-menu flex-grow-1 overflow-y-auto py-2 px-2">
        <div class="sidebar-category-header">CORE</div>
        
        <!-- 1. Dashboard -->
        @if (auth()->user()?->hasPermission('dashboard'))
            <a href="{{ route('dashboard') }}" class="nav-item-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        @endif

        <!-- 2. POS -->
        @if (auth()->user()?->hasPermission('pos'))
            <a href="{{ route('pos.index') }}" class="nav-item-link {{ request()->routeIs('pos.*') ? 'active' : '' }}">
                <i class="bi bi-cart4"></i>
                <span>POS</span>
                <span class="badge bg-success ms-auto small rounded-pill px-2 py-0.5">Live</span>
            </a>
        @endif

        <div class="sidebar-category-header">CATALOG & INVENTORY</div>

        <!-- 3. Products -->
        @if (auth()->user()?->hasPermission('products'))
            <a href="{{ route('products.index') }}" class="nav-item-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                <span>Products</span>
            </a>
        @endif

        <!-- 4. Categories -->
        @if (auth()->user()?->hasPermission('categories'))
            <a href="{{ route('categories.index') }}" class="nav-item-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                <i class="bi bi-tags"></i>
                <span>Categories</span>
            </a>
        @endif

        <!-- 5. Brands -->
        @if (auth()->user()?->hasPermission('brands'))
            <a href="{{ route('brands.index') }}" class="nav-item-link {{ request()->routeIs('brands.*') ? 'active' : '' }}">
                <i class="bi bi-patch-check"></i>
                <span>Brands</span>
            </a>
        @endif

        <!-- 6. Inventory -->
        @if (auth()->user()?->hasPermission('inventory'))
            <a href="{{ route('inventory.index') }}" class="nav-item-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                <i class="bi bi-boxes"></i>
                <span>Inventory</span>
            </a>
        @endif

        <div class="sidebar-category-header">SALES & PROCUREMENT</div>

        <!-- 7. Customers -->
        @if (auth()->user()?->hasPermission('customers'))
            <a href="{{ route('customers.index') }}" class="nav-item-link {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span>Customers</span>
            </a>
        @endif

        <!-- 8. Suppliers -->
        @if (auth()->user()?->hasPermission('suppliers'))
            <a href="{{ route('suppliers.index') }}" class="nav-item-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i>
                <span>Suppliers</span>
            </a>
        @endif

        <!-- 9. Purchases -->
        @if (auth()->user()?->hasPermission('purchases'))
            <a href="{{ route('purchases.index') }}" class="nav-item-link {{ request()->routeIs('purchases.*') ? 'active' : '' }}">
                <i class="bi bi-bag-check"></i>
                <span>Purchases</span>
            </a>
        @endif

        <!-- 10. Sales -->
        @if (auth()->user()?->hasPermission('sales'))
            <a href="{{ route('sales.index') }}" class="nav-item-link {{ request()->routeIs('sales.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i>
                <span>Sales</span>
            </a>
        @endif

        <!-- 10b. Returns -->
        @if (auth()->user()?->hasPermission('sales'))
            <a href="{{ route('returns.index') }}" class="nav-item-link {{ request()->routeIs('returns.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-return-left"></i>
                <span>Returns</span>
            </a>
        @endif

        <!-- 11. Expenses -->
        @if (auth()->user()?->hasPermission('expenses'))
            <a href="{{ route('expenses.index') }}" class="nav-item-link {{ request()->routeIs('expenses.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i>
                <span>Expenses</span>
            </a>
        @endif

        <div class="sidebar-category-header">SYSTEM & ANALYTICS</div>

        <!-- 12. Reports -->
        @if (auth()->user()?->hasPermission('reports'))
            <a href="{{ route('reports.index') }}" class="nav-item-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line"></i>
                <span>Reports</span>
            </a>
        @endif

        <!-- 13. Users & Roles -->
        @if (auth()->user()?->hasPermission('users'))
            <a href="{{ route('users.index') }}" class="nav-item-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i>
                <span>Users</span>
            </a>
            <a href="{{ route('roles.index') }}" class="nav-item-link {{ request()->routeIs('roles.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Roles & Permissions</span>
            </a>
        @endif

        <!-- Notifications -->
        <a href="{{ route('notifications.index') }}" class="nav-item-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
            <i class="bi bi-bell"></i>
            <span>Notifications</span>
            @if (isset($navbarUnreadCount) && $navbarUnreadCount > 0)
                <span class="badge bg-danger ms-auto small rounded-pill px-2 py-0.5">{{ $navbarUnreadCount }}</span>
            @endif
        </a>

        <!-- 14. Settings & Logs -->
        @if (auth()->user()?->hasPermission('settings'))
            <a href="{{ route('activity-logs.index') }}" class="nav-item-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i>
                <span>Activity Logs</span>
            </a>
            <a href="{{ route('settings.index') }}" class="nav-item-link {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
            </a>
        @endif
    </div>

    <!-- Quick Sign Out in Sidebar Footer -->
    <div class="sidebar-footer p-3 border-top border-light-subtle">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-light btn-sm w-100 d-flex align-items-center justify-content-center gap-2 rounded-3 py-2">
                <i class="bi bi-box-arrow-right"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>
</aside>
