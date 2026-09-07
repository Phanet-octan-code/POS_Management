@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="container-fluid p-0" style="max-width: 850px;">
    <div class="mb-4">
        <h3 class="fw-bold text-dark mb-1">Account & Profile Settings</h3>
        <p class="text-muted mb-0">Manage your personal information and update your account password.</p>
    </div>

    <!-- Profile Info Card -->
    <x-card class="mb-4" title="Profile Details" subtitle="Update your personal details">
        <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
            <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center fs-3" style="width: 64px; height: 64px;">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h5 class="fw-bold text-dark mb-1">{{ $user->name }}</h5>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle fw-semibold">
                        <i class="bi bi-shield-check me-1"></i> {{ $user->primaryRoleName() }}
                    </span>
                    <span class="text-muted small"><i class="bi bi-envelope me-1"></i> {{ $user->email }}</span>
                </div>
            </div>
        </div>

        <form action="{{ route('profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="+1 (555) 019-2831">
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">Save Profile</button>
                </div>
            </div>
        </form>
    </x-card>

    <!-- Change Password Card -->
    <x-card title="Security & Password" subtitle="Change your password securely">
        <form action="{{ route('profile.password') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label fw-semibold">Current Password <span class="text-danger">*</span></label>
                    <input type="password" name="current_password" class="form-control" required placeholder="Enter current password">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="8" placeholder="At least 8 characters">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required minlength="8" placeholder="Repeat new password">
                </div>
                <div class="col-12 mt-4">
                    <button type="submit" class="btn btn-warning px-4 fw-bold">Update Password</button>
                </div>
            </div>
        </form>
    </x-card>
</div>
@endsection
