@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="bi bi-truck me-2 text-primary"></i> Suppliers & Vendors</h3>
            <p class="text-muted mb-0">Manage product vendors, procurement sources, and supplier balances.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#createSupplierModal">
            <i class="bi bi-plus-circle me-1"></i> Add Supplier
        </button>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-4">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Suppliers</small>
                        <h4 class="fw-bold text-dark mb-0">{{ $totalSuppliers }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success-subtle text-success p-3 fs-4">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Active Suppliers</small>
                        <h4 class="fw-bold text-success mb-0">{{ $activeSuppliers }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-danger-subtle text-danger p-3 fs-4">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Payable Balance</small>
                        <h4 class="fw-bold text-danger mb-0">${{ number_format($totalPayableBalance, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <x-card class="mb-4">
        <form method="GET" action="{{ route('suppliers.index') }}" class="row g-3 align-items-center">
            <div class="col-md-7 col-lg-8">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by supplier name, company, email, phone, tax ID..." value="{{ request('search') }}">
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
                    <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Suppliers Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th>Supplier / Company</th>
                        <th>Contact</th>
                        <th>Tax Number</th>
                        <th>Payable Balance</th>
                        <th>Purchases</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="supplierTableBody">
                    @forelse ($suppliers as $s)
                        <tr id="supplier-row-{{ $s->id }}">
                            <td>
                                <div class="fw-bold text-dark">
                                    <a href="{{ route('suppliers.show', $s) }}" class="text-dark text-decoration-none hover-primary">
                                        {{ $s->name }}
                                    </a>
                                </div>
                                <small class="text-muted"><i class="bi bi-building me-1"></i>{{ $s->company_name ?? 'Individual' }}</small>
                            </td>
                            <td>
                                <div><i class="bi bi-telephone me-1 text-muted"></i> {{ $s->phone ?? '—' }}</div>
                                <small class="text-muted"><i class="bi bi-envelope me-1"></i> {{ $s->email ?? '—' }}</small>
                            </td>
                            <td>
                                @if($s->tax_number)
                                    <code class="text-dark">{{ $s->tax_number }}</code>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td>
                                @if(($s->balance ?? 0) > 0)
                                    <span class="fw-bold text-danger">${{ number_format($s->balance, 2) }}</span>
                                @else
                                    <span class="text-success small fw-semibold">$0.00 (Clear)</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1.5 rounded-pill">
                                    <i class="bi bi-bag-check me-1"></i> {{ $s->purchases_count }} orders
                                </span>
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm badge-status-btn border-0 p-0 bg-transparent"
                                        onclick="toggleSupplierStatus({{ $s->id }})"
                                        id="supp-status-btn-{{ $s->id }}"
                                        title="Click to toggle status">
                                    <span class="badge {{ $s->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} px-3 py-1.5 rounded-pill">
                                        <i class="bi {{ $s->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }} me-1"></i>
                                        <span id="supp-status-text-{{ $s->id }}">{{ $s->is_active ? 'Active' : 'Inactive' }}</span>
                                    </span>
                                </button>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('suppliers.show', $s) }}" class="btn btn-outline-secondary" title="View Purchases & Profile">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button class="btn btn-outline-primary" onclick="openEditSupplierModal({{ json_encode($s) }})" title="Edit Supplier">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="confirmDeleteSupplier({{ $s->id }}, '{{ addslashes($s->name) }}', {{ $s->purchases_count }})" title="Delete Supplier">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-truck fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <h5>No suppliers found</h5>
                                <p class="small mb-3">Try adjusting your search criteria or register a new vendor.</p>
                                <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#createSupplierModal">
                                    <i class="bi bi-plus-circle me-1"></i> Add First Supplier
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($suppliers->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $suppliers->links() }}
            </div>
        @endif
    </x-card>
</div>

<!-- Create Supplier Modal -->
<div class="modal fade" id="createSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('suppliers.store') }}" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-truck me-1 text-primary"></i> Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Contact / Supplier Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. John Smith">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Company / Business Name</label>
                        <input type="text" name="company_name" class="form-control" placeholder="e.g. Global Logistics Ltd">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="supplier@company.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tax Number / VAT ID</label>
                        <input type="text" name="tax_number" class="form-control" placeholder="e.g. US-TAX-982172">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Initial Payable Balance ($)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" name="balance" class="form-control" value="0.00">
                        </div>
                        <small class="text-muted">Opening balance owed to supplier</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Office / Warehouse Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Warehouse address, city, country..."></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="suppIsActive" checked>
                            <label class="form-check-label fw-semibold" for="suppIsActive">Active Supplier (Available for procurement orders)</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Supplier Modal -->
<div class="modal fade" id="editSupplierModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="editSupplierForm" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-1 text-primary"></i> Edit Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Contact / Supplier Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editSuppName" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Company / Business Name</label>
                        <input type="text" name="company_name" id="editSuppCompany" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" id="editSuppPhone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" id="editSuppEmail" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Tax Number / VAT ID</label>
                        <input type="text" name="tax_number" id="editSuppTax" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Payable Balance ($)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" name="balance" id="editSuppBalance" class="form-control">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Office / Warehouse Address</label>
                        <textarea name="address" id="editSuppAddress" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editSuppIsActive">
                            <label class="form-check-label fw-semibold" for="editSuppIsActive">Active Supplier</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Update Supplier</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditSupplierModal(s) {
        const form = document.getElementById('editSupplierForm');
        form.action = `/suppliers/${s.id}`;
        document.getElementById('editSuppName').value = s.name;
        document.getElementById('editSuppCompany').value = s.company_name || '';
        document.getElementById('editSuppPhone').value = s.phone || '';
        document.getElementById('editSuppEmail').value = s.email || '';
        document.getElementById('editSuppTax').value = s.tax_number || '';
        document.getElementById('editSuppBalance').value = parseFloat(s.balance || 0).toFixed(2);
        document.getElementById('editSuppAddress').value = s.address || '';
        document.getElementById('editSuppIsActive').checked = Boolean(s.is_active);

        const modal = new bootstrap.Modal(document.getElementById('editSupplierModal'));
        modal.show();
    }

    async function toggleSupplierStatus(id) {
        try {
            const res = await fetchJson(`/suppliers/${id}/status`, { method: 'PATCH' });
            if (res.success) {
                const btn = document.getElementById(`supp-status-btn-${id}`);
                if (res.is_active) {
                    btn.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Active</span>`;
                } else {
                    btn.innerHTML = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1.5 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>`;
                }
                Toast.fire({ icon: 'success', title: res.message });
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to toggle supplier status.', 'error');
        }
    }

    function confirmDeleteSupplier(id, name, purchaseCount) {
        let warningText = `Are you sure you want to delete '${name}'?`;
        if (purchaseCount > 0) {
            warningText = `Supplier '${name}' has ${purchaseCount} procurement record(s). Deleting will safely archive this vendor to protect purchase history.`;
        }

        Swal.fire({
            title: 'Delete Supplier?',
            text: warningText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, proceed'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetchJson(`/suppliers/${id}`, { method: 'DELETE' });
                    if (res.success) {
                        const row = document.getElementById(`supplier-row-${id}`);
                        if (row) row.remove();
                        Toast.fire({ icon: 'success', title: res.message });
                    } else {
                        Swal.fire('Error', res.message || 'Failed to delete supplier.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Failed to delete supplier.', 'error');
                }
            }
        });
    }
</script>
@endpush
