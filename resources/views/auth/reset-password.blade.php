<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - {{ config('app.name', 'POS Management') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
        }
    </style>
</head>
<body>

<div class="login-card p-4">
    <div class="text-center mb-4 pt-2">
        <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center p-3 mb-3" style="width: 60px; height: 60px;">
            <i class="bi bi-shield-check fs-3"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">Set New Password</h4>
        <p class="text-muted small mb-0">Create a secure new password for your POS account.</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-0 rounded-3 py-2.5 px-3 small mb-3">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('password.update') }}" method="POST">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-floating mb-3">
            <input type="email" name="email" id="emailInput" class="form-control" value="{{ old('email', $email) }}" required readonly>
            <label for="emailInput">Account Email</label>
        </div>

        <div class="form-floating mb-3">
            <input type="password" name="password" id="passwordInput" class="form-control" placeholder="New Password" required minlength="8" autofocus>
            <label for="passwordInput">New Password (min 8 characters)</label>
        </div>

        <div class="form-floating mb-4">
            <input type="password" name="password_confirmation" id="passwordConfirmInput" class="form-control" placeholder="Confirm Password" required minlength="8">
            <label for="passwordConfirmInput">Confirm New Password</label>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-success fw-bold py-2.5 rounded-3">
                Reset & Update Password
            </button>
        </div>

        <div class="text-center">
            <a href="{{ route('login') }}" class="small text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i> Back to Login
            </a>
        </div>
    </form>
</div>

</body>
</html>
