@extends('layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Roles & Permissions Management</h3>
            <p class="text-muted mb-0">Define role-based access control (RBAC) and assign granular permissions across all 14 POS modules.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-people me-1"></i> Staff Directory
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                <i class="bi bi-shield-plus me-1"></i> Add Custom Role
            </button>
        </div>
    </div>

    <!-- Role Cards Grid -->
    <div class="row g-4 mb-4">
        @foreach ($roles as $role)
            @php
                $isCoreRole = in_array($role->slug, ['admin', 'super-admin', 'manager', 'cashier', 'staff']);
                $themeColor = match($role->slug) {
                    'admin', 'super-admin' => 'danger',
                    'manager' => 'primary',
                    'cashier' => 'success',
                    'staff' => 'warning',
                    default => 'secondary',
                };
                $roleIcon = match($role->slug) {
                    'admin', 'super-admin' => 'bi-shield-check',
                    'manager' => 'bi-briefcase-fill',
                    'cashier' => 'bi-cart4',
                    'staff' => 'bi-box-seam-fill',
                    default => 'bi-person-badge',
                };
            @endphp
            <div class="col-md-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden d-flex flex-column">
                    <!-- Top Ribbon Accent -->
                    <div class="bg-{{ $themeColor }}" style="height: 4px;"></div>

                    <div class="card-body p-4 d-flex flex-column flex-grow-1">
                        <!-- Role Header -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="rounded-3 bg-{{ $themeColor }}-subtle text-{{ $themeColor }} p-2.5 fs-5 d-flex align-items-center justify-content-center"
                                     style="width: 44px; height: 44px;">
                                    <i class="bi {{ $roleIcon }}"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold text-dark mb-0">{{ $role->name }}</h5>
                                    <code class="small text-muted">{{ $role->slug }}</code>
                                </div>
                            </div>
                            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill small">
                                <i class="bi bi-people me-1"></i> {{ $role->users_count }}
                            </span>
                        </div>

                        <!-- Role Description -->
                        <p class="text-muted small mb-3 flex-grow-0" style="min-height: 38px;">
                            {{ $role->description ?: 'System access role with configured permissions.' }}
                        </p>

                        <!-- Granted Permissions Overview -->
                        <div class="mb-3 flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small fw-bold text-uppercase text-muted" style="font-size: 0.72rem; letter-spacing: 0.04em;">
                                    Assigned Modules
                                </span>
                                <span class="badge bg-{{ $themeColor }}-subtle text-{{ $themeColor }} border border-{{ $themeColor }}-subtle rounded-pill small">
                                    {{ $role->slug === 'admin' ? 'All 14' : $role->permissions->count() . ' / 14' }}
                                </span>
                            </div>

                            <div class="d-flex flex-wrap gap-1" style="max-height: 125px; overflow-y: auto;">
                                @if ($role->slug === 'admin')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle small px-2 py-1">
                                        <i class="bi bi-infinity me-1"></i> Full Unrestricted System Access
                                    </span>
                                @else
                                    @forelse ($role->permissions as $perm)
                                        <span class="badge bg-light text-dark border small px-2 py-1">
                                            <i class="bi bi-check2 text-success me-1"></i>{{ $perm->name }}
                                        </span>
                                    @empty
                                        <span class="text-muted small fst-italic">No permissions assigned yet.</span>
                                    @endforelse
                                @endif
                            </div>
                        </div>

                        <!-- Card Action Buttons -->
                        <div class="pt-3 border-top d-flex gap-2 mt-auto">
                            <button class="btn btn-outline-primary btn-sm flex-grow-1 edit-permissions-btn rounded-3 fw-semibold"
                                    data-id="{{ $role->id }}"
                                    data-name="{{ $role->name }}"
                                    data-slug="{{ $role->slug }}"
                                    data-description="{{ $role->description }}"
                                    data-permissions="{{ json_encode($role->permissions->pluck('id')->all()) }}">
                                <i class="bi bi-sliders me-1"></i> Assign Permissions
                            </button>

                            @if (!$isCoreRole)
                                <form action="{{ route('roles.destroy', $role) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete role {{ $role->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-3" title="Delete Role">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- 14 Permissions Reference Matrix Card -->
    <x-card title="System Permissions Specification">
        <p class="text-muted small mb-3">The POS application defines 14 standard functional permissions. Admins can customize permission assignments for any role at any time.</p>
        <div class="row g-3">
            @php
                $allPermList = \App\Models\Permission::orderBy('id')->get();
            @endphp
            @foreach ($allPermList as $p)
                <div class="col-sm-6 col-md-4 col-lg-3">
                    <div class="p-2.5 rounded-3 border bg-light h-100 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <span class="fw-bold text-dark small">{{ $p->name }}</span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small" style="font-size: 0.65rem;">
                                {{ $p->module }}
                            </span>
                        </div>
                        <code class="small text-muted mb-1">{{ $p->slug }}</code>
                        <div class="text-muted small" style="font-size: 0.76rem;">{{ $p->description }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </x-card>
</div>

<!-- ================= MODALS ================= -->

<!-- 1. Edit Role Permissions Modal -->
<div class="modal fade" id="editPermissionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="editPermissionsForm" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-shield-lock text-primary me-2"></i>Assign Permissions: <span id="modalRoleName"></span>
                    </h5>
                    <p class="text-muted small mb-0">Check or uncheck permissions to grant or revoke access for users in this role.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="role_edit_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" name="description" id="role_edit_description" class="form-control">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2 pt-2 border-top">
                    <label class="form-label fw-bold text-dark mb-0">Select Permissions (14 Modules)</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2 rounded-2" id="selectAllPerms">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2 rounded-2" id="deselectAllPerms">Deselect All</button>
                    </div>
                </div>

                <div class="row g-3" style="max-height: 380px; overflow-y: auto;">
                    @foreach ($permissions as $module => $modulePermissions)
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1.5">
                                    <span class="fw-bold text-capitalize text-primary small">
                                        <i class="bi bi-folder2-open me-1"></i> {{ $module }}
                                    </span>
                                    <span class="badge bg-white text-muted border small">{{ count($modulePermissions) }} perm</span>
                                </div>
                                @foreach ($modulePermissions as $p)
                                    <div class="form-check mb-1.5">
                                        <input class="form-check-input edit-perm-checkbox"
                                               type="checkbox"
                                               name="permissions[]"
                                               value="{{ $p->id }}"
                                               id="edit_perm_{{ $p->id }}">
                                        <label class="form-check-label fw-semibold text-dark small" for="edit_perm_{{ $p->id }}">
                                            {{ $p->name }}
                                        </label>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ $p->description }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold px-4">Save Permissions</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Create Custom Role Modal -->
<div class="modal fade" id="createRoleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('roles.store') }}" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-shield-plus text-primary me-2"></i>Create Custom Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Inventory Supervisor">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Brief summary of duties">
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-2 pt-2 border-top">
                    <label class="form-label fw-bold text-dark mb-0">Assign Initial Permissions</label>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary py-0.5 px-2 rounded-2" id="createSelectAll">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary py-0.5 px-2 rounded-2" id="createDeselectAll">Deselect All</button>
                    </div>
                </div>

                <div class="row g-3" style="max-height: 380px; overflow-y: auto;">
                    @foreach ($permissions as $module => $modulePermissions)
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1.5">
                                    <span class="fw-bold text-capitalize text-primary small">
                                        <i class="bi bi-folder2-open me-1"></i> {{ $module }}
                                    </span>
                                </div>
                                @foreach ($modulePermissions as $p)
                                    <div class="form-check mb-1.5">
                                        <input class="form-check-input create-perm-checkbox"
                                               type="checkbox"
                                               name="permissions[]"
                                               value="{{ $p->id }}"
                                               id="create_perm_{{ $p->id }}">
                                        <label class="form-check-label fw-semibold text-dark small" for="create_perm_{{ $p->id }}">
                                            {{ $p->name }}
                                        </label>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ $p->description }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary fw-semibold px-4">Create Role</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editModal = new bootstrap.Modal(document.getElementById('editPermissionsModal'));

    document.querySelectorAll('.edit-permissions-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const description = this.dataset.description;
            const perms = JSON.parse(this.dataset.permissions || '[]');

            document.getElementById('editPermissionsForm').action = `/roles/${id}`;
            document.getElementById('modalRoleName').innerText = name;
            document.getElementById('role_edit_name').value = name;
            document.getElementById('role_edit_description').value = description || '';

            // Check or uncheck permissions
            document.querySelectorAll('.edit-perm-checkbox').forEach(cb => {
                cb.checked = perms.includes(parseInt(cb.value));
            });

            editModal.show();
        });
    });

    // Select All / Deselect All in Edit Modal
    document.getElementById('selectAllPerms')?.addEventListener('click', function () {
        document.querySelectorAll('.edit-perm-checkbox').forEach(cb => cb.checked = true);
    });
    document.getElementById('deselectAllPerms')?.addEventListener('click', function () {
        document.querySelectorAll('.edit-perm-checkbox').forEach(cb => cb.checked = false);
    });

    // Select All / Deselect All in Create Modal
    document.getElementById('createSelectAll')?.addEventListener('click', function () {
        document.querySelectorAll('.create-perm-checkbox').forEach(cb => cb.checked = true);
    });
    document.getElementById('createDeselectAll')?.addEventListener('click', function () {
        document.querySelectorAll('.create-perm-checkbox').forEach(cb => cb.checked = false);
    });
});
</script>
@endpush
@endsection
