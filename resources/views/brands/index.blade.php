@extends('layouts.app')

@section('title', 'Brands')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="bi bi-award me-2 text-primary"></i> Brands</h3>
            <p class="text-muted mb-0">Manage manufacturer brands and product labels.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#createBrandModal">
            <i class="bi bi-plus-circle me-1"></i> Add Brand
        </button>
    </div>

    <!-- Filter & Search Toolbar -->
    <x-card class="mb-4">
        <form method="GET" action="{{ route('brands.index') }}" class="row g-3 align-items-center">
            <div class="col-md-7 col-lg-8">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search brands by name, slug or description..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3 col-lg-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if(request()->hasAny(['search', 'status']))
                    <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Brands Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th>Brand</th>
                        <th>Slug</th>
                        <th>Total Products</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="brandTableBody">
                    @forelse ($brands as $brand)
                        <tr id="brand-row-{{ $brand->id }}">
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="brand-logo-container rounded-3 bg-light border p-1 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                        @if($brand->logo)
                                            <img src="{{ asset('storage/' . $brand->logo) }}" alt="{{ $brand->name }}" class="img-fluid rounded" style="max-height: 36px;">
                                        @else
                                            <i class="bi bi-award text-secondary fs-4"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $brand->name }}</div>
                                        @if($brand->description)
                                            <small class="text-muted text-truncate d-inline-block" style="max-width: 260px;">{{ $brand->description }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td><code>{{ $brand->slug }}</code></td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 rounded-pill">
                                    <i class="bi bi-box-seam me-1"></i> {{ $brand->products_count }} products
                                </span>
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm badge-status-btn border-0 p-0 bg-transparent"
                                        onclick="toggleBrandStatus({{ $brand->id }})"
                                        id="brand-status-btn-{{ $brand->id }}"
                                        title="Click to toggle status">
                                    <span class="badge {{ $brand->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} px-3 py-1.5 rounded-pill">
                                        <i class="bi {{ $brand->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }} me-1"></i>
                                        <span id="brand-status-text-{{ $brand->id }}">{{ $brand->is_active ? 'Active' : 'Inactive' }}</span>
                                    </span>
                                </button>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" 
                                            onclick="openEditBrandModal({{ json_encode($brand) }})"
                                            title="Edit Brand">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="confirmDeleteBrand({{ $brand->id }}, '{{ addslashes($brand->name) }}', {{ $brand->products_count }})"
                                            title="Delete Brand">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-award fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <h5>No brands found</h5>
                                <p class="small mb-3">Try adjusting your search criteria or add a new brand label.</p>
                                <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#createBrandModal">
                                    <i class="bi bi-plus-circle me-1"></i> Create First Brand
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($brands->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $brands->links() }}
            </div>
        @endif
    </x-card>
</div>

<!-- Create Brand Modal -->
<div class="modal fade" id="createBrandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('brands.store') }}" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0 shadow">
            @csrf
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle me-1 text-primary"></i> Add New Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Brand Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Apple, Samsung, Nike, Coca Cola">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Brand Logo</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <small class="text-muted">Optional: PNG, JPG, or WebP up to 2MB.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief manufacturer notes..."></textarea>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createBrandIsActive" checked>
                    <label class="form-check-label fw-semibold" for="createBrandIsActive">Active Brand</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Save Brand</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Brand Modal -->
<div class="modal fade" id="editBrandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editBrandForm" method="POST" enctype="multipart/form-data" class="modal-content rounded-4 border-0 shadow">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-1 text-primary"></i> Edit Brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Brand Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editBrandName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" name="slug" id="editBrandSlug" class="form-control" placeholder="Auto-generated if left blank">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Replace Logo</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <div id="currentLogoPreview" class="mt-2 small text-muted"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" id="editBrandDescription" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editBrandIsActive">
                    <label class="form-check-label fw-semibold" for="editBrandIsActive">Active Brand</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Update Brand</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditBrandModal(brand) {
        const form = document.getElementById('editBrandForm');
        form.action = `/brands/${brand.id}`;
        document.getElementById('editBrandName').value = brand.name;
        document.getElementById('editBrandSlug').value = brand.slug;
        document.getElementById('editBrandDescription').value = brand.description || '';
        document.getElementById('editBrandIsActive').checked = Boolean(brand.is_active);

        const previewContainer = document.getElementById('currentLogoPreview');
        if (brand.logo) {
            previewContainer.innerHTML = `Current logo: <img src="/storage/${brand.logo}" class="rounded border ms-1" style="height: 24px;">`;
        } else {
            previewContainer.innerHTML = 'No logo currently uploaded.';
        }

        const modal = new bootstrap.Modal(document.getElementById('editBrandModal'));
        modal.show();
    }

    async function toggleBrandStatus(id) {
        try {
            const res = await fetchJson(`/brands/${id}/status`, { method: 'PATCH' });
            if (res.success) {
                const btn = document.getElementById(`brand-status-btn-${id}`);
                if (res.is_active) {
                    btn.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Active</span>`;
                } else {
                    btn.innerHTML = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1.5 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>`;
                }
                Toast.fire({ icon: 'success', title: res.message });
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to update brand status.', 'error');
        }
    }

    function confirmDeleteBrand(id, name, productCount) {
        if (productCount > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Delete',
                text: `Brand '${name}' is assigned to ${productCount} products. Please reassign the products before deleting this brand.`
            });
            return;
        }

        Swal.fire({
            title: 'Delete Brand?',
            text: `Are you sure you want to delete '${name}'? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetchJson(`/brands/${id}`, { method: 'DELETE' });
                    if (res.success) {
                        const row = document.getElementById(`brand-row-${id}`);
                        if (row) row.remove();
                        Toast.fire({ icon: 'success', title: res.message });
                    } else {
                        Swal.fire('Error', res.message || 'Failed to delete brand.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Failed to delete brand.', 'error');
                }
            }
        });
    }
</script>
@endpush
