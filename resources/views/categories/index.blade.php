@extends('layouts.app')

@section('title', 'Categories')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="bi bi-tags me-2 text-primary"></i> Categories</h3>
            <p class="text-muted mb-0">Organize your store products into organized departments and categories.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
            <i class="bi bi-plus-circle me-1"></i> Add Category
        </button>
    </div>

    <!-- Filter & Search Toolbar -->
    <x-card class="mb-4">
        <form method="GET" action="{{ route('categories.index') }}" class="row g-3 align-items-center">
            <div class="col-md-7 col-lg-8">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search categories by name, slug or description..." value="{{ request('search') }}">
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
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Categories Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th>Category</th>
                        <th>Parent</th>
                        <th>Slug</th>
                        <th>Total Products</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="categoryTableBody">
                    @forelse ($categories as $cat)
                        <tr id="category-row-{{ $cat->id }}">
                            <td>
                                <div class="fw-bold text-dark">{{ $cat->name }}</div>
                                @if($cat->description)
                                    <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;">{{ $cat->description }}</small>
                                @endif
                            </td>
                            <td>
                                @if($cat->parent)
                                    <span class="badge bg-light text-dark border"><i class="bi bi-arrow-return-right me-1 text-primary"></i> {{ $cat->parent->name }}</span>
                                @else
                                    <span class="text-muted small">None (Root)</span>
                                @endif
                            </td>
                            <td><code>{{ $cat->slug }}</code></td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 rounded-pill">
                                    <i class="bi bi-box-seam me-1"></i> {{ $cat->products_count }} products
                                </span>
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm badge-status-btn border-0 p-0 bg-transparent"
                                        onclick="toggleCategoryStatus({{ $cat->id }})"
                                        id="status-btn-{{ $cat->id }}"
                                        title="Click to toggle status">
                                    <span class="badge {{ $cat->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} px-3 py-1.5 rounded-pill">
                                        <i class="bi {{ $cat->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }} me-1"></i>
                                        <span id="status-text-{{ $cat->id }}">{{ $cat->is_active ? 'Active' : 'Inactive' }}</span>
                                    </span>
                                </button>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" 
                                            onclick="openEditCategoryModal({{ json_encode($cat) }})"
                                            title="Edit Category">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" 
                                            onclick="confirmDeleteCategory({{ $cat->id }}, '{{ addslashes($cat->name) }}', {{ $cat->products_count }})"
                                            title="Delete Category">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-tags fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <h5>No categories found</h5>
                                <p class="small mb-3">Try adjusting your search criteria or add a new category.</p>
                                <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                                    <i class="bi bi-plus-circle me-1"></i> Create First Category
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($categories->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $categories->links() }}
            </div>
        @endif
    </x-card>
</div>

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('categories.store') }}" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-plus-circle me-1 text-primary"></i> Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Beverages, Snacks, Electronics">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Parent Category (Optional)</label>
                    <select name="parent_id" class="form-select">
                        <option value="">None (Top-level Category)</option>
                        @foreach($parentCategories as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief note about what products belong here..."></textarea>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createIsActive" checked>
                    <label class="form-check-label fw-semibold" for="createIsActive">Active Category</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editCategoryForm" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-1 text-primary"></i> Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="editCatName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Slug</label>
                    <input type="text" name="slug" id="editCatSlug" class="form-control" placeholder="Auto-generated if left blank">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Parent Category</label>
                    <select name="parent_id" id="editCatParentId" class="form-select">
                        <option value="">None (Top-level Category)</option>
                        @foreach($parentCategories as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description</label>
                    <textarea name="description" id="editCatDescription" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editCatIsActive">
                    <label class="form-check-label fw-semibold" for="editCatIsActive">Active Category</label>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Update Category</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditCategoryModal(cat) {
        const form = document.getElementById('editCategoryForm');
        form.action = `/categories/${cat.id}`;
        document.getElementById('editCatName').value = cat.name;
        document.getElementById('editCatSlug').value = cat.slug;
        document.getElementById('editCatParentId').value = cat.parent_id || '';
        document.getElementById('editCatDescription').value = cat.description || '';
        document.getElementById('editCatIsActive').checked = Boolean(cat.is_active);

        const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        modal.show();
    }

    async function toggleCategoryStatus(id) {
        try {
            const res = await fetchJson(`/categories/${id}/status`, { method: 'PATCH' });
            if (res.success) {
                const btn = document.getElementById(`status-btn-${id}`);
                const textSpan = document.getElementById(`status-text-${id}`);
                if (res.is_active) {
                    btn.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Active</span>`;
                } else {
                    btn.innerHTML = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1.5 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>`;
                }
                Toast.fire({ icon: 'success', title: res.message });
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to update category status.', 'error');
        }
    }

    function confirmDeleteCategory(id, name, productCount) {
        if (productCount > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Cannot Delete',
                text: `Category '${name}' contains ${productCount} assigned products. Please reassign or delete the products first.`
            });
            return;
        }

        Swal.fire({
            title: 'Delete Category?',
            text: `Are you sure you want to delete '${name}'? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetchJson(`/categories/${id}`, { method: 'DELETE' });
                    if (res.success) {
                        const row = document.getElementById(`category-row-${id}`);
                        if (row) row.remove();
                        Toast.fire({ icon: 'success', title: res.message });
                    } else {
                        Swal.fire('Error', res.message || 'Failed to delete category.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Failed to delete category.', 'error');
                }
            }
        });
    }
</script>
@endpush
