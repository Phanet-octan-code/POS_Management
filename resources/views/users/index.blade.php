@extends('layouts.app')

@section('title', 'User & Staff Management')

@section('content')
<div class="container-fluid p-0">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">User & Staff Management</h3>
            <p class="text-muted mb-0">Control employee access credentials, assign roles, reset passwords, and toggle account activation.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-shield-lock me-1"></i> Manage Roles
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                <i class="bi bi-person-plus-fill me-1"></i> Add User
            </button>
        </div>
    </div>

    <!-- Quick Stat Pills -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary-subtle text-primary p-2 fs-4">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Users</div>
                        <h4 class="fw-bold mb-0">{{ \App\Models\User::count() }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success-subtle text-success p-2 fs-4">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Active Accounts</div>
                        <h4 class="fw-bold mb-0 text-success">{{ \App\Models\User::where('is_active', true)->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info-subtle text-info p-2 fs-4">
                        <i class="bi bi-cart4"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Cashiers</div>
                        <h4 class="fw-bold mb-0">{{ \App\Models\User::whereHas('roles', fn($q) => $q->where('slug', 'cashier'))->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-danger-subtle text-danger p-2 fs-4">
                        <i class="bi bi-person-slash"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Deactivated</div>
                        <h4 class="fw-bold mb-0 text-danger">{{ \App\Models\User::where('is_active', false)->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <x-card class="mb-4">
        <form method="GET" action="{{ route('users.index') }}" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control border-start-0 bg-light" placeholder="Search by name, email, or phone...">
                </div>
            </div>
            <div class="col-md-3">
                <select name="role_id" class="form-select bg-light">
                    <option value="">All Roles</option>
                    @foreach ($roles as $r)
                        <option value="{{ $r->id }}" {{ request('role_id') == $r->id ? 'selected' : '' }}>
                            {{ $r->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select bg-light">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Deactivated</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                @if (request()->hasAny(['q', 'role_id', 'status']))
                    <a href="{{ route('users.index') }}" class="btn btn-light text-muted" title="Reset Filters">
                        <i class="bi bi-x-circle"></i>
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Users Data Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>User Profile</th>
                        <th>Phone</th>
                        <th>Assigned Roles</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr>
                            <td class="text-muted small">{{ $u->id }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2.5">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm"
                                         style="width: 38px; height: 38px; background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%); font-size: 0.9rem;">
                                        {{ strtoupper(substr($u->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $u->name }}</div>
                                        <small class="text-muted">{{ $u->email }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="text-secondary small">{{ $u->phone ?: '—' }}</span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse ($u->roles as $role)
                                        @php
                                            $badgeClass = match($role->slug) {
                                                'admin', 'super-admin' => 'bg-danger-subtle text-danger border-danger-subtle',
                                                'manager' => 'bg-primary-subtle text-primary border-primary-subtle',
                                                'cashier' => 'bg-success-subtle text-success border-success-subtle',
                                                'staff' => 'bg-warning-subtle text-warning-emphasis border-warning-subtle',
                                                default => 'bg-secondary-subtle text-secondary border-secondary-subtle',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} border px-2 py-1 small rounded-pill">
                                            {{ $role->name }}
                                        </span>
                                    @empty
                                        <span class="badge bg-light text-muted border">No Role</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @if ($u->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill">
                                        <i class="bi bi-check-circle-fill me-1"></i> Active
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-pill">
                                        <i class="bi bi-dash-circle-fill me-1"></i> Deactivated
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                {{ $u->created_at ? $u->created_at->format('M d, Y') : '—' }}
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <!-- View Action -->
                                    <button class="btn btn-sm btn-outline-info rounded-3 view-user-btn"
                                            title="View Details"
                                            data-user="{{ json_encode([
                                                'id' => $u->id,
                                                'name' => $u->name,
                                                'email' => $u->email,
                                                'phone' => $u->phone ?? '—',
                                                'is_active' => $u->is_active,
                                                'roles' => $u->roles->pluck('name')->all(),
                                                'created_at' => $u->created_at ? $u->created_at->format('M d, Y h:i A') : '—',
                                                'sales_count' => $u->sales()->count(),
                                                'purchases_count' => $u->purchases()->count(),
                                                'expenses_count' => $u->expenses()->count(),
                                            ]) }}">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Edit Action -->
                                    <button class="btn btn-sm btn-outline-primary rounded-3 edit-user-btn"
                                            title="Edit User"
                                            data-id="{{ $u->id }}"
                                            data-name="{{ $u->name }}"
                                            data-email="{{ $u->email }}"
                                            data-phone="{{ $u->phone }}"
                                            data-is-active="{{ $u->is_active ? '1' : '0' }}"
                                            data-roles="{{ json_encode($u->roles->pluck('id')->all()) }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- Reset Password Action -->
                                    <button class="btn btn-sm btn-outline-warning text-dark rounded-3 reset-pwd-btn"
                                            title="Reset Password"
                                            data-id="{{ $u->id }}"
                                            data-name="{{ $u->name }}">
                                        <i class="bi bi-key"></i>
                                    </button>

                                    <!-- Activate / Deactivate Action -->
                                    @if ($u->id !== 1 && $u->id !== auth()->id())
                                        <form action="{{ route('users.toggle-status', $u) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                    class="btn btn-sm {{ $u->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }} rounded-3"
                                                    title="{{ $u->is_active ? 'Deactivate User' : 'Activate User' }}"
                                                    onclick="return confirm('Are you sure you want to {{ $u->is_active ? 'deactivate' : 'activate' }} this user?')">
                                                <i class="bi {{ $u->is_active ? 'bi-pause-circle' : 'bi-play-circle' }}"></i>
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Delete Action -->
                                    @if ($u->id !== 1 && $u->id !== auth()->id())
                                        <form action="{{ route('users.destroy', $u) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete user {{ $u->name }}? This action cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-3" title="Delete User">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                                <h6 class="fw-bold">No users found</h6>
                                <p class="small mb-0">Try clearing search filters or add a new user.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users</small>
                <div>{{ $users->links() }}</div>
            </div>
        @endif
    </x-card>
</div>

<!-- ================= MODALS ================= -->

<!-- 1. Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('users.store') }}" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus text-primary me-2"></i>Add User Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Alexander Clark">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required placeholder="e.g. alex@example.com">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="+1 (555) 012-3456">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Assign Roles</label>
                    <div class="border rounded-3 p-3 bg-light" style="max-height: 150px; overflow-y: auto;">
                        @foreach ($roles as $r)
                            <div class="form-check mb-1.5">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $r->id }}" id="create_role_{{ $r->id }}">
                                <label class="form-check-label fw-semibold text-dark small" for="create_role_{{ $r->id }}">
                                    {{ $r->name }}
                                </label>
                                <span class="text-muted small ms-1">— {{ $r->description ?? 'System role' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="create_is_active" checked>
                    <label class="form-check-label fw-semibold" for="create_is_active">Active Account</label>
                    <div class="text-muted small">Active users can sign into the POS terminal and management portal.</div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold px-4">Create Account</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editUserForm" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i>Edit User Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="edit_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Phone Number</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Password (Leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control" minlength="8" placeholder="Optional new password">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Assign Roles</label>
                    <div class="border rounded-3 p-3 bg-light" style="max-height: 150px; overflow-y: auto;">
                        @foreach ($roles as $r)
                            <div class="form-check mb-1.5">
                                <input class="form-check-input edit-role-checkbox" type="checkbox" name="roles[]" value="{{ $r->id }}" id="edit_role_{{ $r->id }}">
                                <label class="form-check-label fw-semibold text-dark small" for="edit_role_{{ $r->id }}">
                                    {{ $r->name }}
                                </label>
                                <span class="text-muted small ms-1">— {{ $r->description ?? 'System role' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active">
                    <label class="form-check-label fw-semibold" for="edit_is_active">Active Account</label>
                    <div class="text-muted small">Deactivated users cannot log into the POS system.</div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold px-4">Update Account</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. View User Modal -->
<div class="modal fade" id="viewUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-badge text-info me-2"></i>User Profile Overview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4">
                <div class="text-center mb-4">
                    <div id="view_avatar" class="rounded-circle d-inline-flex align-items-center justify-content-center text-white fw-bold shadow mb-2"
                         style="width: 64px; height: 64px; font-size: 1.5rem; background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);">
                        U
                    </div>
                    <h5 id="view_name" class="fw-bold mb-0 text-dark">User Name</h5>
                    <p id="view_email" class="text-muted small mb-2">user@example.com</p>
                    <div id="view_status_container"></div>
                </div>

                <div class="list-group list-group-flush rounded-3 border">
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                        <span class="text-muted small"><i class="bi bi-telephone me-2"></i>Phone</span>
                        <span id="view_phone" class="fw-semibold small text-dark">—</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                        <span class="text-muted small"><i class="bi bi-shield me-2"></i>Roles</span>
                        <span id="view_roles" class="small">—</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                        <span class="text-muted small"><i class="bi bi-calendar me-2"></i>Date Joined</span>
                        <span id="view_created_at" class="fw-semibold small text-dark">—</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                        <span class="text-muted small"><i class="bi bi-receipt me-2"></i>Sales Processed</span>
                        <span id="view_sales" class="badge bg-light text-dark border">—</span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center py-2.5">
                        <span class="text-muted small"><i class="bi bi-bag-check me-2"></i>Purchases Created</span>
                        <span id="view_purchases" class="badge bg-light text-dark border">—</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-secondary w-100 rounded-3" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- 4. Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="resetPasswordForm" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-key text-warning me-2"></i>Reset User Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="text-muted small mb-3">
                    Set a new password for <strong id="reset_pwd_username" class="text-dark"></strong>.
                </p>
                <div class="mb-3">
                    <label class="form-label fw-semibold">New Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required minlength="8" placeholder="Re-type password">
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-warning fw-semibold px-4">Reset Password</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Edit User Modal Handling
    const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
    document.querySelectorAll('.edit-user-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const email = this.dataset.email;
            const phone = this.dataset.phone;
            const isActive = this.dataset.isActive === '1';
            const roles = JSON.parse(this.dataset.roles || '[]');

            document.getElementById('editUserForm').action = `/users/${id}`;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_phone').value = phone || '';
            document.getElementById('edit_is_active').checked = isActive;

            // Set role checkboxes
            document.querySelectorAll('.edit-role-checkbox').forEach(cb => {
                cb.checked = roles.includes(parseInt(cb.value));
            });

            editModal.show();
        });
    });

    // View User Modal Handling
    const viewModal = new bootstrap.Modal(document.getElementById('viewUserModal'));
    document.querySelectorAll('.view-user-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const u = JSON.parse(this.dataset.user);
            document.getElementById('view_avatar').innerText = u.name.charAt(0).toUpperCase();
            document.getElementById('view_name').innerText = u.name;
            document.getElementById('view_email').innerText = u.email;
            document.getElementById('view_phone').innerText = u.phone;
            document.getElementById('view_created_at').innerText = u.created_at;
            document.getElementById('view_sales').innerText = `${u.sales_count} orders`;
            document.getElementById('view_purchases').innerText = `${u.purchases_count} purchases`;

            // Status badge
            const statusContainer = document.getElementById('view_status_container');
            if (u.is_active) {
                statusContainer.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> Active</span>';
            } else {
                statusContainer.innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-pill"><i class="bi bi-dash-circle-fill me-1"></i> Deactivated</span>';
            }

            // Roles list
            const rolesContainer = document.getElementById('view_roles');
            if (u.roles && u.roles.length > 0) {
                rolesContainer.innerHTML = u.roles.map(r => `<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1 rounded-pill">${r}</span>`).join('');
            } else {
                rolesContainer.innerHTML = '<span class="text-muted small">No Role Assigned</span>';
            }

            viewModal.show();
        });
    });

    // Reset Password Modal Handling
    const resetModal = new bootstrap.Modal(document.getElementById('resetPasswordModal'));
    document.querySelectorAll('.reset-pwd-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;

            document.getElementById('resetPasswordForm').action = `/users/${id}/reset-password`;
            document.getElementById('reset_pwd_username').innerText = name;

            resetModal.show();
        });
    });
});
</script>
@endpush
@endsection
