@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="bi bi-people me-2 text-primary"></i> Customer Management</h3>
            <p class="text-muted mb-0">Track customer profiles, purchase records, credit limits, and balances.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#createCustomerModal">
            <i class="bi bi-person-plus me-1"></i> Add Customer
        </button>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-4">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Customers</small>
                        <h4 class="fw-bold text-dark mb-0">{{ $totalCustomers }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning-subtle text-warning p-3 fs-4">
                        <i class="bi bi-gem"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">VIP Members</small>
                        <h4 class="fw-bold text-dark mb-0">{{ $totalVip }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-info-subtle text-info p-3 fs-4">
                        <i class="bi bi-shop"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Wholesale Buyers</small>
                        <h4 class="fw-bold text-dark mb-0">{{ $totalWholesale }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-danger-subtle text-danger p-3 fs-4">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Balance Due</small>
                        <h4 class="fw-bold text-danger mb-0">${{ number_format($totalReceivableBalance, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <x-card class="mb-4">
        <form method="GET" action="{{ route('customers.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5 col-lg-6">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, phone, email, or address..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3 col-lg-2">
                <select name="type" class="form-select">
                    <option value="">All Customer Types</option>
                    <option value="Regular" {{ request('type') === 'Regular' ? 'selected' : '' }}>Regular</option>
                    <option value="VIP" {{ request('type') === 'VIP' ? 'selected' : '' }}>VIP</option>
                    <option value="Wholesale" {{ request('type') === 'Wholesale' ? 'selected' : '' }}>Wholesale</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-2">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if(request()->hasAny(['search', 'type', 'status']))
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Customers Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Type</th>
                        <th>Credit Limit</th>
                        <th>Balance Due</th>
                        <th>Purchases</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="customerTableBody">
                    @forelse ($customers as $c)
                        <tr id="customer-row-{{ $c->id }}">
                            <td>
                                <div class="fw-bold text-dark">
                                    <a href="{{ route('customers.show', $c) }}" class="text-dark text-decoration-none hover-primary">
                                        {{ $c->name }}
                                    </a>
                                </div>
                                <div class="small text-muted">
                                    @if($c->gender)
                                        <span class="text-capitalize me-1"><i class="bi bi-person me-0.5"></i>{{ $c->gender }}</span> &bull;
                                    @endif
                                    {{ $c->address ?? 'No address' }}
                                </div>
                            </td>
                            <td>
                                <div><i class="bi bi-telephone me-1 text-muted"></i> {{ $c->phone ?? '—' }}</div>
                                <small class="text-muted"><i class="bi bi-envelope me-1"></i> {{ $c->email ?? '—' }}</small>
                            </td>
                            <td>
                                @if($c->type === 'VIP')
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1.5 rounded-pill fw-bold">
                                        <i class="bi bi-gem me-1"></i> VIP
                                    </span>
                                @elseif($c->type === 'Wholesale')
                                    <span class="badge bg-info-subtle text-info border border-info-subtle px-2.5 py-1.5 rounded-pill fw-bold">
                                        <i class="bi bi-boxes me-1"></i> Wholesale
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2.5 py-1.5 rounded-pill">
                                        Regular
                                    </span>
                                @endif
                            </td>
                            <td class="text-secondary">${{ number_format($c->credit_limit ?? 0, 2) }}</td>
                            <td>
                                @if(($c->balance ?? 0) > 0)
                                    <span class="fw-bold text-danger">${{ number_format($c->balance, 2) }}</span>
                                @else
                                    <span class="text-success small fw-semibold">$0.00 (Clear)</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">${{ number_format($c->total_spent, 2) }}</div>
                                <small class="text-muted">{{ $c->sales_count }} sales</small>
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm badge-status-btn border-0 p-0 bg-transparent"
                                        onclick="toggleCustomerStatus({{ $c->id }})"
                                        id="cust-status-btn-{{ $c->id }}"
                                        title="Click to toggle status">
                                    <span class="badge {{ $c->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} px-3 py-1.5 rounded-pill">
                                        <i class="bi {{ $c->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }} me-1"></i>
                                        <span id="cust-status-text-{{ $c->id }}">{{ $c->is_active ? 'Active' : 'Inactive' }}</span>
                                    </span>
                                </button>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('customers.show', $c) }}" class="btn btn-outline-secondary" title="View Purchase History & Profile">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button class="btn btn-outline-primary" onclick="openEditCustomerModal({{ json_encode($c) }})" title="Edit Customer">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="confirmDeleteCustomer({{ $c->id }}, '{{ addslashes($c->name) }}', {{ $c->sales_count }})" title="Delete Customer">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <h5>No customers found</h5>
                                <p class="small mb-3">Try adjusting your search criteria or register a new customer.</p>
                                <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#createCustomerModal">
                                    <i class="bi bi-person-plus me-1"></i> Add First Customer
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $customers->links() }}
            </div>
        @endif
    </x-card>
</div>

<!-- Create Customer Modal -->
<div class="modal fade" id="createCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form action="{{ route('customers.store') }}" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus me-1 text-primary"></i> Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Customer Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. John Doe">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="customer@example.com">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Customer Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="Regular" selected>Regular</option>
                            <option value="VIP">VIP</option>
                            <option value="Wholesale">Wholesale</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">Unspecified</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Credit Limit ($)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" min="0" name="credit_limit" class="form-control" value="0.00">
                        </div>
                        <small class="text-muted">Maximum allowed unpaid balance</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Initial Balance Due ($)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" name="balance" class="form-control" value="0.00">
                        </div>
                        <small class="text-muted">Opening outstanding balance if any</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Billing / Delivery Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Street, city, postal code..."></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="custIsActive" checked>
                            <label class="form-check-label fw-semibold" for="custIsActive">Active Account (Allowed to purchase in POS)</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form id="editCustomerForm" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-1 text-primary"></i> Edit Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Customer Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editCustName" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="text" name="phone" id="editCustPhone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Email Address</label>
                        <input type="email" name="email" id="editCustEmail" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Customer Type <span class="text-danger">*</span></label>
                        <select name="type" id="editCustType" class="form-select" required>
                            <option value="Regular">Regular</option>
                            <option value="VIP">VIP</option>
                            <option value="Wholesale">Wholesale</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Gender</label>
                        <select name="gender" id="editCustGender" class="form-select">
                            <option value="">Unspecified</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Credit Limit ($)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" min="0" name="credit_limit" id="editCustCreditLimit" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Balance Due ($)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">$</span>
                            <input type="number" step="0.01" name="balance" id="editCustBalance" class="form-control">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Billing / Delivery Address</label>
                        <textarea name="address" id="editCustAddress" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editCustIsActive">
                            <label class="form-check-label fw-semibold" for="editCustIsActive">Active Account</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">Update Customer</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function openEditCustomerModal(c) {
        const form = document.getElementById('editCustomerForm');
        form.action = `/customers/${c.id}`;
        document.getElementById('editCustName').value = c.name;
        document.getElementById('editCustPhone').value = c.phone || '';
        document.getElementById('editCustEmail').value = c.email || '';
        document.getElementById('editCustType').value = c.type || 'Regular';
        document.getElementById('editCustGender').value = c.gender || '';
        document.getElementById('editCustCreditLimit').value = parseFloat(c.credit_limit || 0).toFixed(2);
        document.getElementById('editCustBalance').value = parseFloat(c.balance || 0).toFixed(2);
        document.getElementById('editCustAddress').value = c.address || '';
        document.getElementById('editCustIsActive').checked = Boolean(c.is_active);

        const modal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
        modal.show();
    }

    async function toggleCustomerStatus(id) {
        try {
            const res = await fetchJson(`/customers/${id}/status`, { method: 'PATCH' });
            if (res.success) {
                const btn = document.getElementById(`cust-status-btn-${id}`);
                if (res.is_active) {
                    btn.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Active</span>`;
                } else {
                    btn.innerHTML = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1.5 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>`;
                }
                Toast.fire({ icon: 'success', title: res.message });
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to toggle customer status.', 'error');
        }
    }

    function confirmDeleteCustomer(id, name, salesCount) {
        let warningText = `Are you sure you want to delete '${name}'?`;
        if (salesCount > 0) {
            warningText = `Customer '${name}' has ${salesCount} transaction record(s). Deleting will archive the customer safely to maintain accounting history.`;
        }

        Swal.fire({
            title: 'Delete Customer?',
            text: warningText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, proceed'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetchJson(`/customers/${id}`, { method: 'DELETE' });
                    if (res.success) {
                        const row = document.getElementById(`customer-row-${id}`);
                        if (row) row.remove();
                        Toast.fire({ icon: 'success', title: res.message });
                    } else {
                        Swal.fire('Error', res.message || 'Failed to delete customer.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Failed to delete customer.', 'error');
                }
            }
        });
    }
</script>
@endpush
