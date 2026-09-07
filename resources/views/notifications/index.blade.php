@extends('layouts.app')

@section('title', 'Notification Center')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Notification Center</h3>
            <p class="text-muted mb-0">Live stock shortage warnings, sales transactions, purchases, payments, and system alerts.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if ($totalUnread > 0)
                <form action="{{ route('notifications.markAllRead') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-primary btn-sm px-3 fw-bold rounded-pill">
                        <i class="bi bi-check2-all me-1"></i> Mark All as Read ({{ $totalUnread }})
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Metric Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Unread -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Unread Alerts</small>
                        <h4 class="fw-bold mb-0 text-danger mt-1">{{ $totalUnread }}</h4>
                    </div>
                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-bell-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Stock Alerts -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Stock Alerts</small>
                        <h4 class="fw-bold mb-0 text-warning mt-1">{{ $stockAlertsCount }}</h4>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Sales & Payments -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Sales & Payments</small>
                        <h4 class="fw-bold mb-0 text-success mt-1">{{ $salesCount + $paymentsCount }}</h4>
                    </div>
                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-cart-check-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Purchases & Expenses -->
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-white">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.72rem;">Purchases & Expenses</small>
                        <h4 class="fw-bold mb-0 text-primary mt-1">{{ $purchasesCount + $expensesCount }}</h4>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="bi bi-bag-check-fill fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Critical Low Stock Immediate Action Banner -->
    @if ($criticalProducts->isNotEmpty())
        <div class="card border-0 border-start border-4 border-danger shadow-sm rounded-4 p-3 mb-4 bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-danger text-white p-2.5 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 42px; height: 42px;">
                        <i class="bi bi-shield-exclamation fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-0.5">Critical Inventory Shortages ({{ $criticalProducts->count() }} items)</h6>
                        <small class="text-muted">
                            Products at zero or below safety threshold:
                            @foreach ($criticalProducts->take(3) as $cp)
                                <span class="badge bg-light text-danger border me-1">{{ $cp->name }} ({{ $cp->stock_quantity }})</span>
                            @endforeach
                            @if ($criticalProducts->count() > 3)
                                <span class="text-muted">+{{ $criticalProducts->count() - 3 }} more</span>
                            @endif
                        </small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="bi bi-box-seam me-1"></i> View Stock
                    </a>
                    <a href="{{ route('purchases.create') }}" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold">
                        <i class="bi bi-bag-plus me-1"></i> Create Purchase Order
                    </a>
                </div>
            </div>
        </div>
    @endif

    <!-- Filter Pills Toolbar -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <!-- Type Filters -->
            <div class="d-flex flex-wrap gap-1">
                <a href="{{ route('notifications.index', ['type' => 'all', 'status' => request('status', 'all')]) }}"
                   class="btn btn-sm {{ $type === 'all' ? 'btn-dark' : 'btn-light border text-muted' }} rounded-pill px-3 fw-semibold">
                    All
                </a>
                <a href="{{ route('notifications.index', ['type' => 'stock_alerts', 'status' => request('status', 'all')]) }}"
                   class="btn btn-sm {{ $type === 'stock_alerts' ? 'btn-warning text-dark fw-bold' : 'btn-light border text-muted' }} rounded-pill px-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Stock Alerts
                </a>
                <a href="{{ route('notifications.index', ['type' => 'new_sale', 'status' => request('status', 'all')]) }}"
                   class="btn btn-sm {{ $type === 'new_sale' ? 'btn-success fw-bold' : 'btn-light border text-muted' }} rounded-pill px-3">
                    <i class="bi bi-cart-check-fill me-1"></i> Sales
                </a>
                <a href="{{ route('notifications.index', ['type' => 'payment', 'status' => request('status', 'all')]) }}"
                   class="btn btn-sm {{ $type === 'payment' ? 'btn-info text-white fw-bold' : 'btn-light border text-muted' }} rounded-pill px-3">
                    <i class="bi bi-cash-coin me-1"></i> Payments
                </a>
                <a href="{{ route('notifications.index', ['type' => 'new_purchase', 'status' => request('status', 'all')]) }}"
                   class="btn btn-sm {{ $type === 'new_purchase' ? 'btn-primary fw-bold' : 'btn-light border text-muted' }} rounded-pill px-3">
                    <i class="bi bi-bag-check-fill me-1"></i> Purchases
                </a>
                <a href="{{ route('notifications.index', ['type' => 'expense', 'status' => request('status', 'all')]) }}"
                   class="btn btn-sm {{ $type === 'expense' ? 'btn-secondary fw-bold' : 'btn-light border text-muted' }} rounded-pill px-3">
                    <i class="bi bi-wallet2 me-1"></i> Expenses
                </a>
            </div>

            <!-- Read / Unread Status Filter -->
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('notifications.index', ['type' => $type, 'status' => 'all']) }}"
                   class="btn {{ $status === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    All Status
                </a>
                <a href="{{ route('notifications.index', ['type' => $type, 'status' => 'unread']) }}"
                   class="btn {{ $status === 'unread' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Unread Only
                </a>
                <a href="{{ route('notifications.index', ['type' => $type, 'status' => 'read']) }}"
                   class="btn {{ $status === 'read' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    Read
                </a>
            </div>
        </div>
    </div>

    <!-- Notification Feed Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                <div class="list-group-item p-3.5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 transition-all {{ $notification->is_unread ? 'bg-light-subtle border-start border-3 border-danger' : '' }}">
                    <div class="d-flex align-items-start gap-3">
                        <!-- Icon Circle -->
                        <div class="rounded-circle bg-{{ $notification->color }}-subtle text-{{ $notification->color }} d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="bi {{ $notification->icon }} fs-5"></i>
                        </div>

                        <!-- Notification Info -->
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                <h6 class="fw-bold mb-0 text-dark">{{ $notification->title }}</h6>
                                <span class="badge bg-{{ $notification->color }}-subtle text-{{ $notification->color }} border border-{{ $notification->color }}-subtle rounded-pill px-2 py-0.5" style="font-size: 0.7rem;">
                                    {{ $notification->type_label }}
                                </span>
                                @if ($notification->is_unread)
                                    <span class="badge bg-danger rounded-pill px-2 py-0.5" style="font-size: 0.65rem;">
                                        UNREAD
                                    </span>
                                @endif
                            </div>
                            <p class="text-secondary small mb-1">{{ $notification->message }}</p>
                            <small class="text-muted d-flex align-items-center gap-2">
                                <span><i class="bi bi-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}</span>
                                <span>&bull;</span>
                                <span>{{ $notification->created_at->format('M d, Y - H:i') }}</span>
                            </small>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex align-items-center gap-2 ms-auto ms-md-0">
                        @if (!empty($notification->link))
                            <a href="{{ $notification->link }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                                View Details <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        @endif

                        @if ($notification->is_unread)
                            <form action="{{ route('notifications.markRead', $notification->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-light border rounded-pill px-3 text-muted" title="Mark as Read">
                                    <i class="bi bi-check2"></i> Mark Read
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-bell-slash fs-1 d-block mb-2 text-secondary opacity-50"></i>
                    <h5 class="fw-bold text-dark mb-1">No notifications found</h5>
                    <p class="small text-muted mb-0">No records match your selected filter criteria.</p>
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="card-footer bg-light border-top p-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing {{ $notifications->firstItem() ?? 0 }} to {{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }} alerts
                </small>
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
