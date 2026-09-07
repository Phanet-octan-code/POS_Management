<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>POS Terminal - {{ config('app.name', 'POS Management') }}</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            height: 100vh;
            overflow: hidden;
        }

        .pos-header {
            background-color: #0f172a;
            color: #ffffff;
            height: 60px;
            padding: 0 1.5rem;
        }

        .pos-body {
            height: calc(100vh - 60px);
        }

        .product-grid-container {
            height: 100%;
            overflow-y: auto;
            padding: 1.25rem;
        }

        .cart-container {
            background: #ffffff;
            border-left: 1px solid #e2e8f0;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .cart-items {
            flex-grow: 1;
            overflow-y: auto;
        }

        .product-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #ffffff;
        }

        .product-card:hover {
            transform: translateY(-2px);
            border-color: #4f46e5;
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.12);
        }

        .cart-summary {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 1.25rem;
        }
    </style>
</head>
<body>
    <!-- POS Navigation Bar -->
    <header class="pos-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            @if (auth()->user()?->hasRole(['admin', 'manager']))
                <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            @endif
            <h5 class="mb-0 fw-bold"><i class="bi bi-shop me-2 text-primary"></i> POS Terminal</h5>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill d-none d-md-inline-flex align-items-center" title="Google Firebase Firestore: pos-management-88866">
                <i class="bi bi-fire text-warning me-1.5"></i> Firebase Cloud Sync
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill">
                <i class="bi bi-person-fill me-1"></i> {{ auth()->user()?->name ?? 'Cashier' }} ({{ auth()->user()?->primaryRoleName() }})
            </span>
            <a href="{{ route('profile.index') }}" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="bi bi-person me-1"></i> Profile
            </a>
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">
                    <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                </button>
            </form>
        </div>
    </header>

    <div class="pos-body container-fluid p-0">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
            timerProgressBar: true
        });
    </script>

    <!-- Firebase Web SDK Integration -->
    @vite(['resources/js/app.js'])
    <script type="module" src="{{ asset('js/firebase-init.js') }}"></script>

    @stack('scripts')
</body>
</html>
