<!-- Top Navbar -->
<header class="pos-navbar bg-white border-bottom border-light-subtle sticky-top">
    <div class="container-fluid px-3 px-lg-4 py-2.5 d-flex align-items-center justify-content-between">
        <!-- Left: Mobile Sidebar Toggle & Page Info -->
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-light d-lg-none p-2 rounded-3 border" id="sidebarToggleBtn" aria-label="Toggle Sidebar">
                <i class="bi bi-list fs-5"></i>
            </button>
            <div class="d-none d-sm-block">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill fw-semibold">
                        <i class="bi bi-broadcast me-1"></i> Register Ready
                    </span>
                    <span class="text-muted small d-none d-md-inline">|</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill fw-semibold d-none d-lg-inline-flex align-items-center" title="Google Firebase Project: pos-management-88866">
                        <i class="bi bi-fire text-warning me-1"></i> Firebase Connected
                    </span>
                    <span class="text-muted small d-none d-md-inline">|</span>
                    <span class="fw-semibold text-secondary small d-none d-md-inline"><i class="bi bi-shop me-1 text-primary"></i>{{ $appSettings['store_name'] ?? 'OmniPOS Store' }}</span>
                    <span class="text-muted small d-none d-md-inline">|</span>
                    <span class="text-muted small d-none d-md-inline" id="navbarClock">{{ date('l, M d, Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Center: Quick Global Search Input -->
        <div class="d-none d-md-block flex-grow-1 mx-4" style="max-width: 400px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0 text-muted rounded-start-pill ps-3">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control bg-light border-start-0 rounded-end-pill py-2" placeholder="Search orders, SKU, customers...">
            </div>
        </div>

        <!-- Right: POS Quick Launch, Notifications & User Dropdown -->
        <div class="d-flex align-items-center gap-2.5">
            <!-- POS Quick Launch Button -->
            @if (auth()->user()?->hasRole(['admin', 'cashier']))
                <a href="{{ route('pos.index') }}" class="btn btn-pos-quick d-flex align-items-center gap-2 rounded-pill px-3 py-1.5 text-decoration-none">
                    <i class="bi bi-cart-plus-fill"></i>
                    <span class="fw-bold d-none d-sm-inline">POS Register</span>
                </a>
            @endif

            <!-- Notification Menu Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light position-relative rounded-pill p-2 border" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                    <i class="bi bi-bell fs-5 text-secondary"></i>
                    @if (isset($navbarUnreadCount) && $navbarUnreadCount > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                            {{ $navbarUnreadCount }}
                        </span>
                    @endif
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-0" style="width: 360px; max-width: 92vw;" aria-labelledby="notificationDropdown">
                    <div class="p-3 border-bottom d-flex align-items-center justify-content-between bg-light rounded-top-4">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold mb-0 text-dark">Notifications</h6>
                            @if (isset($navbarUnreadCount) && $navbarUnreadCount > 0)
                                <span class="badge bg-danger rounded-pill">{{ $navbarUnreadCount }} new</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @if (isset($navbarUnreadCount) && $navbarUnreadCount > 0)
                                <form action="{{ route('notifications.markAllRead') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-link text-decoration-none p-0 small text-muted" title="Mark all as read">
                                        <i class="bi bi-check2-all me-1"></i>Mark Read
                                    </button>
                                </form>
                                <span class="text-muted small">|</span>
                            @endif
                            <a href="{{ route('notifications.index') }}" class="small text-decoration-none text-primary fw-semibold">View All</a>
                        </div>
                    </div>
                    <div class="list-group list-group-flush" style="max-height: 320px; overflow-y: auto;">
                        @if (isset($navbarNotifications) && $navbarNotifications->isNotEmpty())
                            @foreach ($navbarNotifications as $notif)
                                <a href="{{ $notif->link }}" class="list-group-item list-group-item-action p-3 d-flex gap-2.5 align-items-start {{ $notif->is_unread ? 'bg-light-subtle' : '' }}">
                                    <div class="rounded-circle bg-{{ $notif->color }}-subtle text-{{ $notif->color }} p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                        <i class="bi {{ $notif->icon }}"></i>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="d-flex justify-content-between align-items-center mb-0.5">
                                            <span class="fw-bold text-dark small text-truncate">{{ $notif->title }}</span>
                                            <small class="text-muted ms-1 text-nowrap" style="font-size: 0.7rem;">{{ $notif->created_at->diffForHumans(null, true, true) }}</small>
                                        </div>
                                        <p class="mb-0 text-secondary small lh-sm text-truncate-2" style="font-size: 0.78rem;">{{ $notif->message }}</p>
                                    </div>
                                </a>
                            @endforeach
                        @elseif (isset($navbarStockAlerts) && $navbarStockAlerts->isNotEmpty())
                            @foreach ($navbarStockAlerts as $alert)
                                <a href="{{ route('inventory.index') }}" class="list-group-item list-group-item-action p-3 d-flex gap-2.5 align-items-start">
                                    <div class="rounded-circle bg-danger-subtle text-danger p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small">{{ $alert->name }}</div>
                                        <div class="text-danger small fw-semibold">Low stock: only {{ $alert->stock_quantity }} {{ $alert->unit }} left</div>
                                        <small class="text-muted">SKU: {{ $alert->sku }}</small>
                                    </div>
                                </a>
                            @endforeach
                        @else
                            <div class="p-4 text-center text-muted">
                                <i class="bi bi-bell-slash fs-2 d-block mb-1 text-secondary opacity-50"></i>
                                <span class="small">No new notifications</span>
                            </div>
                        @endif
                    </div>
                    <div class="p-2.5 text-center border-top bg-light rounded-bottom-4">
                        <a href="{{ route('notifications.index') }}" class="small text-muted text-decoration-none fw-semibold">
                            Open Notification Center <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- User Menu Dropdown -->
            <div class="dropdown">
                <button class="btn btn-light d-flex align-items-center gap-2 rounded-pill px-2.5 py-1.5 border" id="userMenuBtn" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar-circle rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
                        {{ strtoupper(substr(auth()->user()?->name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="text-start d-none d-sm-block me-1">
                        <span class="small fw-bold d-block text-dark lh-1">{{ auth()->user()?->name ?? 'Staff' }}</span>
                        <span class="user-role-badge badge bg-primary-subtle text-primary border border-primary-subtle px-1.5 py-0.5" style="font-size: 0.65rem;">
                            {{ auth()->user()?->primaryRoleName() ?? 'User' }}
                        </span>
                    </div>
                    <i class="bi bi-chevron-down text-muted small"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-2" style="min-width: 220px;" aria-labelledby="userMenuBtn">
                    <li class="px-3 py-2 border-bottom mb-2">
                        <div class="fw-bold text-dark">{{ auth()->user()?->name }}</div>
                        <small class="text-muted d-block text-truncate">{{ auth()->user()?->email }}</small>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2" href="{{ route('profile.index') }}">
                            <i class="bi bi-person-circle text-primary"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    @if (auth()->user()?->isAdmin())
                        <li>
                            <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2" href="{{ route('settings.index') }}">
                                <i class="bi bi-gear text-secondary"></i>
                                <span>Store Settings</span>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 d-flex align-items-center gap-2" href="{{ route('activity-logs.index') }}">
                                <i class="bi bi-clock-history text-secondary"></i>
                                <span>Activity Logs</span>
                            </a>
                        </li>
                    @endif
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item rounded-3 py-2 text-danger d-flex align-items-center gap-2">
                                <i class="bi bi-box-arrow-right"></i>
                                <span>Sign Out</span>
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
