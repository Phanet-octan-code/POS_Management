<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - {{ config('app.name', 'POS Management') }}</title>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-card {
            background: #ffffff;
            border-radius: 1.25rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .login-header {
            padding: 2.25rem 2.25rem 1.25rem;
            text-align: center;
        }

        .brand-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            border-radius: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.35);
        }

        .form-floating:focus-within {
            z-index: 2;
        }

        .btn-login {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            color: #ffffff;
            font-weight: 700;
            padding: 0.8rem 1.5rem;
            border-radius: 0.75rem;
            border: none;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
            transition: all 0.2s;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #4338ca, #3730a3);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.45);
            color: #ffffff;
        }

        .demo-badge {
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.78rem;
            padding: 0.4rem 0.65rem;
        }

        .demo-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="brand-icon">
            <i class="bi bi-shop"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">POS Management</h4>
        <p class="text-muted small mb-0">Sign in to access your store terminal and management dashboard.</p>
    </div>

    <div class="p-4 pt-2">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 rounded-3 py-2.5 px-3 small mb-3">
                <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 rounded-3 py-2.5 px-3 small mb-3">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.post') }}" method="POST">
            @csrf

            <div class="form-floating mb-3">
                <input type="email" name="email" id="emailInput" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="name@example.com" required autofocus>
                <label for="emailInput"><i class="bi bi-envelope me-1 text-muted"></i> Email address</label>
            </div>

            <div class="form-floating mb-3">
                <input type="password" name="password" id="passwordInput" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required>
                <label for="passwordInput"><i class="bi bi-shield-lock me-1 text-muted"></i> Password</label>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="rememberMe">
                    <label class="form-check-label small text-muted" for="rememberMe">Remember me</label>
                </div>
                <a href="{{ route('password.request') }}" class="small text-decoration-none text-primary fw-semibold">Forgot Password?</a>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to POS
                </button>
            </div>
        </form>

        <!-- Quick Demo Switcher -->
        <div class="border-top pt-3 mt-4">
            <small class="text-muted fw-bold d-block text-center text-uppercase mb-2" style="font-size: 0.7rem; letter-spacing: 0.05em;">
                Quick Demo Accounts (Click to Fill)
            </small>
            <div class="d-flex flex-wrap gap-1.5 justify-content-center">
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle demo-badge rounded-pill" onclick="fillCredentials('admin@pos.com', 'password')">
                    <i class="bi bi-person-fill-gear me-1"></i> Admin
                </span>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle demo-badge rounded-pill" onclick="fillCredentials('manager@pos.com', 'password')">
                    <i class="bi bi-briefcase-fill me-1"></i> Manager
                </span>
                <span class="badge bg-success-subtle text-success border border-success-subtle demo-badge rounded-pill" onclick="fillCredentials('cashier@pos.com', 'password')">
                    <i class="bi bi-cart4 me-1"></i> Cashier
                </span>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle demo-badge rounded-pill" onclick="fillCredentials('staff@pos.com', 'password')">
                    <i class="bi bi-person-badge me-1"></i> Staff
                </span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function fillCredentials(email, password) {
        document.getElementById('emailInput').value = email;
        document.getElementById('passwordInput').value = password;
    }
</script>
</body>
</html>
