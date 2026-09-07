<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'POS Management') }}</title>

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --pos-primary: #4338ca;
            --pos-primary-hover: #3730a3;
            --pos-accent: #10b981;
            --pos-sidebar-bg: #0f172a;
            --pos-sidebar-active: #4338ca;
            --pos-sidebar-hover: #1e293b;
            --pos-body-bg: #f8fafc;
            --pos-card-border: #e2e8f0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--pos-body-bg);
            color: #1e293b;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ----------------- Layout Frame ----------------- */
        .pos-wrapper {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        /* ----------------- Sidebar Styling ----------------- */
        .pos-sidebar {
            width: 275px;
            background-color: var(--pos-sidebar-bg);
            min-height: 100vh;
            position: sticky;
            top: 0;
            z-index: 1040;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-brand-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.35);
            border-radius: 10px;
        }

        .sidebar-brand-badge {
            width: 42px;
            height: 42px;
            min-width: 42px;
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
            padding: 4px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.25);
        }

        .sidebar-brand-title {
            font-size: 1.05rem;
            font-weight: 700;
            letter-spacing: -0.2px;
            line-height: 1.25;
            color: #ffffff;
        }

        .sidebar-category-header {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.09em;
            color: #64748b;
            padding: 0.95rem 1rem 0.4rem;
            text-transform: uppercase;
        }

        .nav-item-link {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            padding: 0.65rem 0.95rem;
            color: #94a3b8;
            font-weight: 500;
            font-size: 0.88rem;
            border-radius: 0.6rem;
            margin-bottom: 0.25rem;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .nav-item-link:hover {
            color: #ffffff;
            background-color: var(--pos-sidebar-hover);
        }

        .nav-item-link.active {
            color: #ffffff;
            background: linear-gradient(135deg, var(--pos-primary), #6366f1);
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(67, 56, 202, 0.35);
        }

        .nav-item-link i {
            font-size: 1.2rem;
            width: 1.5rem;
            min-width: 1.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            line-height: 1;
        }

        /* ----------------- Main Content Wrapper ----------------- */
        .pos-main {
            flex-grow: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            background-color: var(--pos-body-bg);
        }

        /* ----------------- Top Navbar Styling ----------------- */
        .pos-navbar {
            z-index: 1020;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .btn-pos-quick {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #ffffff !important;
            font-weight: 600;
            font-size: 0.85rem;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            border: none;
            transition: all 0.2s ease;
        }

        .btn-pos-quick:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.4);
        }

        /* ----------------- Mobile Responsive Overlay ----------------- */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 1035;
            transition: opacity 0.3s ease;
        }

        @media (max-width: 991.98px) {
            .pos-sidebar {
                position: fixed;
                left: 0;
                top: 0;
                bottom: 0;
                transform: translateX(-100%);
            }

            .pos-sidebar.show {
                transform: translateX(0);
                box-shadow: 0 20px 30px rgba(0, 0, 0, 0.35);
            }

            .sidebar-backdrop.show {
                display: block;
            }
        }

        /* ----------------- Clean Cards & Typography ----------------- */
        .card {
            border: 1px solid var(--pos-card-border);
            border-radius: 0.85rem;
        }

        .table th {
            font-weight: 600;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .table td {
            font-size: 0.9rem;
        }
    </style>

    @stack('styles')
</head>
<body>

    <div class="pos-wrapper">
        <!-- Sidebar Navigation -->
        @include('layouts.sidebar')

        <!-- Mobile Backdrop Overlay -->
        <div id="sidebarBackdrop" class="sidebar-backdrop"></div>

        <!-- Main Content Area -->
        <div class="pos-main">
            <!-- Top Navbar -->
            @include('layouts.navbar')

            <!-- Main Page Content -->
            <main class="flex-grow-1 p-3 p-lg-4">
                @if (session('success'))
                    <x-alert type="success">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    </x-alert>
                @endif

                @if ($errors->any())
                    <x-alert type="danger">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-alert>
                @endif

                @yield('content')
            </main>

            <!-- Footer -->
            <footer class="bg-white border-top border-light-subtle py-3 px-4 text-center text-muted small">
                &copy; {{ date('Y') }} <strong>OmniPOS Management System</strong>. Built with Laravel 12 & Bootstrap 5.
            </footer>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Global Fetch API helper configured with CSRF
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function fetchJson(url, options = {}) {
            options.headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                ...(options.headers || {})
            };
            const response = await fetch(url, options);
            return response.json();
        }

        // Global Toast Notification
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });

        // Mobile Sidebar Toggle Logic
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const toggleBtn = document.getElementById('sidebarToggleBtn');
            const closeBtn = document.getElementById('sidebarCloseBtn');

            function openSidebar() {
                sidebar.classList.add('show');
                backdrop.classList.add('show');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
                document.body.style.overflow = '';
            }

            if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
            if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
            if (backdrop) backdrop.addEventListener('click', closeSidebar);
        });
    </script>

    <!-- Firebase Web SDK Integration -->
    @vite(['resources/js/app.js'])
    <script type="module" src="{{ asset('js/firebase-init.js') }}"></script>

    @stack('scripts')
</body>
</html>
