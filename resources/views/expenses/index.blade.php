@extends('layouts.app')

@section('title', 'Expense Management')

@section('content')
<div class="container-fluid p-0">
    <!-- Header Row -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-wallet2 me-2 text-danger"></i> Expense Management
            </h3>
            <p class="text-muted mb-0">Record, search, and monitor operational costs and track their direct impact on store net profit.</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#createExpenseModal">
            <i class="bi bi-plus-circle me-1"></i> Record Expense
        </button>
    </div>

    <!-- Financial KPI Summary Cards (Net Profit Impact) -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Filtered Expenses"
                value="${{ number_format($filteredTotal, 2) }}"
                icon="bi-wallet2"
                color="danger"
                subtext="All-time: ${{ number_format($allTimeTotal, 2) }}"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Period Gross Sales"
                value="${{ number_format($profitMetrics['total_sales'], 2) }}"
                icon="bi-cash-coin"
                color="primary"
                subtext="{{ $profitMetrics['total_orders'] }} orders in period"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Cost of Goods (COGS)"
                value="${{ number_format($profitMetrics['net_cogs'], 2) }}"
                icon="bi-box-arrow-right"
                color="secondary"
                subtext="Net inventory costs sold"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Period Net Profit"
                value="${{ number_format($profitMetrics['net_profit'], 2) }}"
                icon="bi-graph-up-arrow"
                color="{{ $profitMetrics['net_profit'] >= 0 ? 'success' : 'danger' }}"
                subtext="Sales - COGS - Expenses"
                badge="{{ $profitMetrics['net_profit'] >= 0 ? '+Profitable' : '-Deficit' }}"
            />
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <x-card class="mb-4">
        <form method="GET" action="{{ route('expenses.index') }}" class="row g-3 align-items-end">
            <!-- Search Query -->
            <div class="col-md-4 col-lg-3">
                <label class="form-label small fw-semibold text-muted mb-1">Search</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Expense name, ref#, notes, user..." value="{{ $filters['search'] ?? '' }}">
                </div>
            </div>

            <!-- Category Filter -->
            <div class="col-md-3 col-lg-3">
                <label class="form-label small fw-semibold text-muted mb-1">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All Categories ({{ $categories->count() }})</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (string) ($filters['category_id'] ?? '') === (string) $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Start Date -->
            <div class="col-md-2 col-lg-2">
                <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] ?? '' }}">
            </div>

            <!-- End Date -->
            <div class="col-md-2 col-lg-2">
                <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
            </div>

            <!-- Filter & Reset Buttons -->
            <div class="col-md-1 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
                @if(!empty($filters['search']) || !empty($filters['category_id']) || !empty($filters['start_date']) || !empty($filters['end_date']) || !empty($filters['user_id']))
                    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary" title="Clear Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Expenses Data Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th style="width: 14%;">Ref #</th>
                        <th style="width: 12%;">Date</th>
                        <th style="width: 26%;">Expense Name & Description</th>
                        <th style="width: 14%;">Category</th>
                        <th style="width: 12%;" class="text-end">Amount</th>
                        <th style="width: 12%;">Recorded By</th>
                        <th style="width: 10%;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $exp)
                        <tr>
                            <td>
                                <code class="fw-bold text-dark">{{ $exp->reference_no }}</code>
                            </td>
                            <td>
                                <span class="text-dark">{{ $exp->expense_date->format('M d, Y') }}</span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $exp->title }}</div>
                                @if($exp->notes)
                                    <small class="text-muted d-block text-truncate" style="max-width: 320px;">
                                        {{ $exp->notes }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $exp->category_color }} px-2 py-1">
                                    {{ $exp->category?->name ?? 'Uncategorized' }}
                                </span>
                            </td>
                            <td class="text-end fw-bold text-danger fs-6">
                                ${{ number_format($exp->amount, 2) }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-1.5">
                                    <i class="bi bi-person-circle text-muted small"></i>
                                    <span class="small text-dark">{{ $exp->user?->name ?? 'Staff' }}</span>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <!-- View Action -->
                                    <button type="button" class="btn btn-outline-info view-expense-btn"
                                        title="View Expense Details"
                                        data-id="{{ $exp->id }}"
                                        data-ref="{{ $exp->reference_no }}"
                                        data-name="{{ $exp->title }}"
                                        data-category="{{ $exp->category?->name ?? 'Uncategorized' }}"
                                        data-color="{{ $exp->category_color }}"
                                        data-amount="${{ number_format($exp->amount, 2) }}"
                                        data-date="{{ $exp->expense_date->format('M d, Y') }}"
                                        data-user="{{ $exp->user?->name ?? 'Staff' }}"
                                        data-notes="{{ $exp->notes ?? 'No description provided.' }}"
                                        data-created="{{ $exp->created_at?->format('M d, Y H:i:s') }}">
                                        <i class="bi bi-eye"></i>
                                    </button>

                                    <!-- Edit Action -->
                                    <button type="button" class="btn btn-outline-primary edit-expense-btn"
                                        title="Edit Expense"
                                        data-id="{{ $exp->id }}"
                                        data-title="{{ $exp->title }}"
                                        data-category-id="{{ $exp->expense_category_id }}"
                                        data-amount="{{ $exp->amount }}"
                                        data-date="{{ $exp->expense_date->format('Y-m-d') }}"
                                        data-user-id="{{ $exp->user_id }}"
                                        data-notes="{{ $exp->notes }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <!-- Delete Action -->
                                    <form action="{{ route('expenses.destroy', $exp) }}" method="POST" class="d-inline delete-expense-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-outline-danger delete-btn" title="Delete Expense">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-wallet2 fs-1 text-muted d-block mb-2"></i>
                                <div class="fw-semibold">No expenses found matching your criteria.</div>
                                <small class="text-muted">Try clearing your filters or record a new operating expense.</small>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
            <div class="p-3 border-top">
                {{ $expenses->links() }}
            </div>
        @endif
    </x-card>
</div>

<!-- ==================== CREATE EXPENSE MODAL ==================== -->
<div class="modal fade" id="createExpenseModal" tabindex="-1" aria-labelledby="createExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('expenses.store') }}" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            <div class="modal-header border-bottom pb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-dark" id="createExpenseModalLabel">Record New Expense</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Expense Name -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Expense Name <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Office Electric Bill, Store Rent, Staff Payroll" value="{{ old('title') }}">
                </div>

                <!-- Category -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <select name="expense_category_id" class="form-select" required>
                        <option value="">-- Select Expense Category --</option>
                        @foreach ($categories as $ec)
                            <option value="{{ $ec->id }}" {{ old('expense_category_id') == $ec->id ? 'selected' : '' }}>
                                {{ $ec->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Amount & Date -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Amount ($) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required placeholder="0.00" value="{{ old('amount') }}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', date('Y-m-d')) }}" required>
                    </div>
                </div>

                <!-- User / Staff -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Recorded By (User) <span class="text-danger">*</span></label>
                    <select name="user_id" class="form-select" required>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" {{ old('user_id', auth()->id()) == $u->id ? 'selected' : '' }}>
                                {{ $u->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Description / Notes -->
                <div class="mb-0">
                    <label class="form-label fw-semibold">Description / Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes, invoice or receipt memo...">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="modal-footer border-top pt-3">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-check-circle me-1"></i> Record Expense
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== EDIT EXPENSE MODAL ==================== -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="editExpenseForm" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            @method('PUT')
            <div class="modal-header border-bottom pb-3">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-warning-subtle text-warning rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="bi bi-pencil"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-dark" id="editExpenseModalLabel">Edit Expense</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Expense Name -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Expense Name <span class="text-danger">*</span></label>
                    <input type="text" id="edit_title" name="title" class="form-control" required>
                </div>

                <!-- Category -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                    <select id="edit_category_id" name="expense_category_id" class="form-select" required>
                        @foreach ($categories as $ec)
                            <option value="{{ $ec->id }}">{{ $ec->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Amount & Date -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Amount ($) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0.01" id="edit_amount" name="amount" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" id="edit_date" name="expense_date" class="form-control" required>
                    </div>
                </div>

                <!-- User / Staff -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Recorded By (User) <span class="text-danger">*</span></label>
                    <select id="edit_user_id" name="user_id" class="form-select" required>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Description / Notes -->
                <div class="mb-0">
                    <label class="form-label fw-semibold">Description / Notes</label>
                    <textarea id="edit_notes" name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer border-top pt-3">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-save me-1"></i> Update Expense
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== VIEW EXPENSE MODAL ==================== -->
<div class="modal fade" id="viewExpenseModal" tabindex="-1" aria-labelledby="viewExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom pb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Voucher</span>
                    <h5 class="modal-title fw-bold text-dark" id="viewExpenseModalLabel">Expense Details</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Amount Banner -->
                <div class="p-3 bg-light rounded-3 text-center mb-4">
                    <small class="text-muted text-uppercase fw-semibold d-block">Expense Amount</small>
                    <div id="view_amount" class="display-6 fw-bold text-danger my-1">$0.00</div>
                    <span id="view_ref" class="badge bg-secondary font-monospace">EXP-0000-000000</span>
                </div>

                <!-- Details Grid -->
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Expense Name</small>
                        <strong id="view_name" class="text-dark fs-6">-</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Category</small>
                        <span id="view_category" class="badge bg-primary fs-6 px-2.5 py-1 mt-1">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Expense Date</small>
                        <span id="view_date" class="text-dark fw-semibold">-</span>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block text-uppercase fw-semibold" style="font-size: 0.75rem;">Recorded By</small>
                        <span id="view_user" class="text-dark fw-semibold">-</span>
                    </div>
                </div>

                <!-- Description -->
                <div class="p-3 bg-light rounded-3 mb-3">
                    <small class="text-muted d-block text-uppercase fw-semibold mb-1" style="font-size: 0.75rem;">Description / Notes</small>
                    <div id="view_notes" class="text-dark small">-</div>
                </div>

                <!-- Audit info -->
                <div class="text-muted small text-end">
                    <span>Created on: </span><span id="view_created">-</span>
                </div>
            </div>
            <div class="modal-footer border-top pt-3">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Handle Edit Modal Population
    const editModal = new bootstrap.Modal(document.getElementById('editExpenseModal'));
    const editForm = document.getElementById('editExpenseForm');

    document.querySelectorAll('.edit-expense-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.dataset.id;
            const title = this.dataset.title;
            const categoryId = this.dataset.categoryId;
            const amount = this.dataset.amount;
            const date = this.dataset.date;
            const userId = this.dataset.userId;
            const notes = this.dataset.notes;

            editForm.action = `/expenses/${id}`;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_category_id').value = categoryId;
            document.getElementById('edit_amount').value = parseFloat(amount).toFixed(2);
            document.getElementById('edit_date').value = date;
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_notes').value = notes || '';

            editModal.show();
        });
    });

    // 2. Handle View Modal Population
    const viewModal = new bootstrap.Modal(document.getElementById('viewExpenseModal'));

    document.querySelectorAll('.view-expense-btn').forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('view_ref').textContent = this.dataset.ref;
            document.getElementById('view_amount').textContent = this.dataset.amount;
            document.getElementById('view_name').textContent = this.dataset.name;
            
            const catBadge = document.getElementById('view_category');
            catBadge.textContent = this.dataset.category;
            catBadge.className = `badge bg-${this.dataset.color} fs-6 px-2.5 py-1 mt-1`;

            document.getElementById('view_date').textContent = this.dataset.date;
            document.getElementById('view_user').textContent = this.dataset.user;
            document.getElementById('view_notes').textContent = this.dataset.notes;
            document.getElementById('view_created').textContent = this.dataset.created;

            viewModal.show();
        });
    });

    // 3. Handle Delete with SweetAlert2
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.closest('form');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Expense?',
                    text: "This expense record will be soft deleted and removed from Net Profit calculations.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            } else {
                if (confirm('Delete this expense entry? It will be removed from Net Profit calculations.')) {
                    form.submit();
                }
            }
        });
    });
});
</script>
@endpush
