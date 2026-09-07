@extends('layouts.app')

@section('title', 'Inventory & Stock Management')

@section('content')
<div class="container-fluid p-0">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">
                <i class="bi bi-boxes me-2 text-primary"></i> Inventory & Stock Management
            </h3>
            <p class="text-muted mb-0">Track real-time stock levels, inventory valuations, low stock alerts, and audit movements.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('inventory.history') }}" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                <i class="bi bi-clock-history me-1"></i> Stock Movement History
            </a>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#stockAdjustModal">
                <i class="bi bi-sliders me-1"></i> Adjust Stock
            </button>
        </div>
    </div>

    <!-- Inventory KPI Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-4">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Stock Units</small>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($totalStockUnits) }}</h4>
                        <small class="text-muted">{{ $totalProducts }} total catalog items</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success-subtle text-success p-3 fs-4">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Stock Value (Cost)</small>
                        <h4 class="fw-bold text-success mb-0">${{ number_format($totalCostValuation, 2) }}</h4>
                        <small class="text-muted">Retail: ${{ number_format($totalRetailValuation, 2) }}</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100 {{ $lowStockCount > 0 ? 'border-start border-warning border-4' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning-subtle text-warning p-3 fs-4">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Low Stock Items</small>
                        <h4 class="fw-bold text-warning mb-0">{{ $lowStockCount }}</h4>
                        <a href="{{ route('inventory.index', ['stock_status' => 'low_stock']) }}" class="small text-decoration-none fw-semibold">
                            View low stock &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100 {{ $outOfStockCount > 0 ? 'border-start border-danger border-4' : '' }}">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-danger-subtle text-danger p-3 fs-4">
                        <i class="bi bi-x-circle"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Out of Stock</small>
                        <h4 class="fw-bold text-danger mb-0">{{ $outOfStockCount }}</h4>
                        <a href="{{ route('inventory.index', ['stock_status' => 'out_of_stock']) }}" class="small text-danger text-decoration-none fw-semibold">
                            View out of stock &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters & Search Toolbar -->
    <x-card class="mb-4">
        <form method="GET" action="{{ route('inventory.index') }}" class="row g-2 align-items-center">
            <div class="col-md-5 col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search product name, SKU, or barcode..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3 col-lg-3">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-lg-2">
                <select name="stock_status" class="form-select">
                    <option value="">All Stock Statuses</option>
                    <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>In Stock</option>
                    <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                    <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                </select>
            </div>
            <div class="col-md-2 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if(request()->hasAny(['search', 'category_id', 'stock_status']))
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Inventory Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th style="min-width: 220px;">Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th class="text-center">Current Stock</th>
                        <th class="text-center">Minimum Stock</th>
                        <th class="text-end">Purchase Price</th>
                        <th class="text-end">Selling Price</th>
                        <th class="text-end">Stock Value</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="min-width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $p)
                        <tr>
                            <!-- Product Name & Thumbnail -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="rounded-3 border object-fit-cover shadow-xs" style="width: 44px; height: 44px;">
                                    <div>
                                        <div class="fw-bold text-dark">{{ $p->name }}</div>
                                        @if($p->brand)
                                            <small class="text-muted">{{ $p->brand->name }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- SKU -->
                            <td>
                                <span class="badge bg-light text-dark border font-monospace">{{ $p->sku }}</span>
                            </td>

                            <!-- Category -->
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    {{ $p->category?->name ?? 'Uncategorized' }}
                                </span>
                            </td>

                            <!-- Current Stock -->
                            <td class="text-center">
                                <span class="fw-bold fs-6 {{ $p->isOutOfStock() ? 'text-danger' : ($p->isLowStock() ? 'text-warning' : 'text-dark') }}">
                                    {{ number_format($p->stock_quantity) }}
                                </span>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">{{ $p->unit ?? 'Units' }}</small>
                            </td>

                            <!-- Minimum Stock -->
                            <td class="text-center">
                                <span class="text-secondary fw-semibold">{{ number_format($p->alert_quantity) }}</span>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">Alert Threshold</small>
                            </td>

                            <!-- Purchase Price -->
                            <td class="text-end fw-semibold text-muted">
                                ${{ number_format($p->cost_price, 2) }}
                            </td>

                            <!-- Selling Price -->
                            <td class="text-end fw-semibold text-dark">
                                ${{ number_format($p->selling_price, 2) }}
                            </td>

                            <!-- Stock Value -->
                            <td class="text-end fw-bold text-primary">
                                ${{ number_format($p->stock_value, 2) }}
                            </td>

                            <!-- Status Badge: In Stock, Low Stock, Out of Stock -->
                            <td class="text-center">
                                @if ($p->isOutOfStock())
                                    <span class="badge bg-danger-subtle text-danger fw-bold border border-danger-subtle px-2 py-1 rounded-pill">
                                        <i class="bi bi-x-circle me-1"></i> Out of Stock
                                    </span>
                                @elseif ($p->isLowStock())
                                    <span class="badge bg-warning-subtle text-warning fw-bold border border-warning-subtle px-2 py-1 rounded-pill">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Low Stock
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success fw-bold border border-success-subtle px-2 py-1 rounded-pill">
                                        <i class="bi bi-check-circle me-1"></i> In Stock
                                    </span>
                                @endif
                            </td>

                            <!-- Row Action Buttons -->
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <!-- Quick Add Button -->
                                    <button type="button"
                                            class="btn btn-outline-success"
                                            title="Add Stock (+)"
                                            onclick="openQuickAddModal({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $p->stock_quantity }}, '{{ $p->unit }}')">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                    <!-- Quick Remove Button -->
                                    <button type="button"
                                            class="btn btn-outline-danger"
                                            title="Remove Stock (-)"
                                            onclick="openQuickRemoveModal({{ $p->id }}, '{{ addslashes($p->name) }}', {{ $p->stock_quantity }}, '{{ $p->unit }}')">
                                        <i class="bi bi-dash-lg"></i>
                                    </button>
                                    <!-- Full Adjust Button -->
                                    <button type="button"
                                            class="btn btn-outline-primary"
                                            title="Adjust Stock Details"
                                            onclick="openAdjustModalForProduct({{ $p->id }}, {{ $p->stock_quantity }})">
                                        <i class="bi bi-sliders"></i>
                                    </button>
                                    <!-- Movement History Shortcut -->
                                    <a href="{{ route('inventory.history', ['product_id' => $p->id]) }}"
                                       class="btn btn-outline-secondary"
                                       title="View Product Movement History">
                                        <i class="bi bi-clock-history"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-boxes fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <h5>No Products Match Your Criteria</h5>
                                <p class="small text-muted mb-0">Try changing your search terms or clearing the filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <small class="text-muted">Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} items</small>
                {{ $products->links() }}
            </div>
        @endif
    </x-card>
</div>

<!-- ========================================== -->
<!-- Full Stock Adjustment Modal -->
<!-- ========================================== -->
<div class="modal fade" id="stockAdjustModal" tabindex="-1" aria-labelledby="stockAdjustModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('inventory.adjust') }}" method="POST" id="stockAdjustForm" class="modal-content rounded-4 border-0 shadow">
            @csrf
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="stockAdjustModalLabel">
                    <i class="bi bi-sliders me-2 text-primary"></i> Stock Adjustment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Select Product -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-uppercase">Select Product <span class="text-danger">*</span></label>
                    <select name="product_id" id="adjust_product_id" class="form-select" required onchange="onProductSelectChange()">
                        <option value="">-- Choose a product --</option>
                        @foreach ($allProducts as $pr)
                            <option value="{{ $pr->id }}" data-stock="{{ $pr->stock_quantity }}" data-unit="{{ $pr->unit }}">
                                {{ $pr->name }} (SKU: {{ $pr->sku }} | Current: {{ $pr->stock_quantity }} {{ $pr->unit }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Adjustment Mode: Add / Remove / Set Exact -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-uppercase">Adjustment Mode <span class="text-danger">*</span></label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="mode" id="mode_add" value="add" checked onchange="onModeChange()">
                        <label class="btn btn-outline-success" for="mode_add"><i class="bi bi-plus-circle me-1"></i> Add Stock</label>

                        <input type="radio" class="btn-check" name="mode" id="mode_remove" value="remove" onchange="onModeChange()">
                        <label class="btn btn-outline-danger" for="mode_remove"><i class="bi bi-dash-circle me-1"></i> Remove Stock</label>

                        <input type="radio" class="btn-check" name="mode" id="mode_set" value="set" onchange="onModeChange()">
                        <label class="btn btn-outline-primary" for="mode_set"><i class="bi bi-arrow-repeat me-1"></i> Set Count</label>
                    </div>
                </div>

                <!-- Specific Movement Type -->
                <div class="mb-3" id="type_container">
                    <label class="form-label fw-semibold small text-uppercase">Movement Category</label>
                    <select name="type" id="adjust_type" class="form-select">
                        <option value="in">Restock / Addition</option>
                        <option value="return">Customer Return</option>
                        <option value="adjustment">Physical Audit Discrepancy</option>
                        <option value="out">Inventory Withdrawal</option>
                        <option value="damage">Damaged / Expired Goods</option>
                        <option value="loss">Inventory Shrinkage / Lost</option>
                    </select>
                </div>

                <!-- Quantity -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-uppercase" id="quantity_label">
                        Quantity to Add <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="number" name="quantity" id="adjust_quantity" class="form-control form-control-lg" min="1" required placeholder="0" oninput="calculateProjectedStock()">
                        <span class="input-group-text bg-light text-muted" id="adjust_unit_label">units</span>
                    </div>
                </div>

                <!-- Live Preview of Projected Stock -->
                <div class="card bg-light border-0 p-3 mb-3 rounded-3" id="stockPreviewCard">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted d-block">Current Stock:</small>
                            <span class="fw-bold fs-6 text-dark" id="preview_current_stock">—</span>
                        </div>
                        <i class="bi bi-arrow-right fs-4 text-muted"></i>
                        <div>
                            <small class="text-muted d-block">Projected New Stock:</small>
                            <span class="fw-bold fs-6 text-primary" id="preview_new_stock">—</span>
                        </div>
                    </div>
                </div>

                <!-- Reason / Remark -->
                <div class="mb-0">
                    <label class="form-label fw-semibold small text-uppercase">Reason / Notes <span class="text-danger">*</span></label>
                    <textarea name="reason" id="adjust_reason" class="form-control" rows="2" required placeholder="Reason for inventory adjustment (e.g. supplier restock, damaged during shelf stocking, quarterly physical audit)..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold" id="submitAdjustBtn">
                    <i class="bi bi-check2-circle me-1"></i> Apply Adjustment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- Quick Add Stock Modal -->
<!-- ========================================== -->
<div class="modal fade" id="quickAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form action="{{ route('inventory.quick-add') }}" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            <input type="hidden" name="product_id" id="quick_add_product_id">
            <div class="modal-header border-bottom bg-success-subtle text-success">
                <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle me-1"></i> Quick Add Stock</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="fw-bold text-dark mb-1" id="quick_add_name"></div>
                <small class="text-muted d-block mb-3">Current: <span id="quick_add_curr" class="fw-bold text-dark"></span></small>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Quantity to Add</label>
                    <input type="number" name="quantity" class="form-control" min="1" required placeholder="e.g. 10">
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-semibold">Reason (Optional)</label>
                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Delivery received">
                </div>
            </div>
            <div class="modal-footer p-2 bg-light">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-success px-3"><i class="bi bi-plus-lg me-1"></i> Add Stock</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- Quick Remove Stock Modal -->
<!-- ========================================== -->
<div class="modal fade" id="quickRemoveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form action="{{ route('inventory.quick-remove') }}" method="POST" class="modal-content rounded-4 border-0 shadow">
            @csrf
            <input type="hidden" name="product_id" id="quick_remove_product_id">
            <div class="modal-header border-bottom bg-danger-subtle text-danger">
                <h6 class="modal-title fw-bold"><i class="bi bi-dash-circle me-1"></i> Quick Remove Stock</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="fw-bold text-dark mb-1" id="quick_remove_name"></div>
                <small class="text-muted d-block mb-3">Current: <span id="quick_remove_curr" class="fw-bold text-dark"></span></small>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Quantity to Deduct</label>
                    <input type="number" name="quantity" id="quick_remove_qty" class="form-control" min="1" required placeholder="e.g. 5">
                </div>
                <div class="mb-0">
                    <label class="form-label small fw-semibold">Reason (Optional)</label>
                    <input type="text" name="reason" class="form-control form-control-sm" placeholder="e.g. Damaged item / wastage">
                </div>
            </div>
            <div class="modal-footer p-2 bg-light">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-danger px-3"><i class="bi bi-dash-lg me-1"></i> Deduct</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Live calculation for Stock Adjustment Modal
    function onProductSelectChange() {
        const select = document.getElementById('adjust_product_id');
        const opt = select.options[select.selectedIndex];
        const unit = opt.dataset.unit || 'units';
        document.getElementById('adjust_unit_label').textContent = unit;
        calculateProjectedStock();
    }

    function onModeChange() {
        const mode = document.querySelector('input[name="mode"]:checked').value;
        const qtyLabel = document.getElementById('quantity_label');
        const typeSelect = document.getElementById('adjust_type');

        if (mode === 'add') {
            qtyLabel.innerHTML = 'Quantity to Add <span class="text-danger">*</span>';
            typeSelect.value = 'in';
        } else if (mode === 'remove') {
            qtyLabel.innerHTML = 'Quantity to Deduct <span class="text-danger">*</span>';
            typeSelect.value = 'out';
        } else {
            qtyLabel.innerHTML = 'New Total Stock Count <span class="text-danger">*</span>';
            typeSelect.value = 'adjustment';
        }
        calculateProjectedStock();
    }

    function calculateProjectedStock() {
        const select = document.getElementById('adjust_product_id');
        const currSpan = document.getElementById('preview_current_stock');
        const newSpan = document.getElementById('preview_new_stock');

        if (!select.value) {
            currSpan.textContent = '—';
            newSpan.textContent = '—';
            return;
        }

        const opt = select.options[select.selectedIndex];
        const currentStock = parseInt(opt.dataset.stock, 10) || 0;
        const unit = opt.dataset.unit || 'units';
        currSpan.textContent = currentStock + ' ' + unit;

        const qtyInput = document.getElementById('adjust_quantity');
        const qty = parseInt(qtyInput.value, 10);

        if (isNaN(qty)) {
            newSpan.textContent = '—';
            return;
        }

        const mode = document.querySelector('input[name="mode"]:checked').value;
        let projected = currentStock;

        if (mode === 'add') {
            projected = currentStock + Math.abs(qty);
        } else if (mode === 'remove') {
            projected = currentStock - Math.abs(qty);
        } else {
            projected = qty;
        }

        if (projected < 0) {
            newSpan.innerHTML = `<span class="text-danger">${projected} ${unit} (Invalid - Cannot be negative)</span>`;
            document.getElementById('submitAdjustBtn').disabled = true;
        } else {
            newSpan.innerHTML = `<span class="text-success fw-bold">${projected} ${unit}</span>`;
            document.getElementById('submitAdjustBtn').disabled = false;
        }
    }

    function openAdjustModalForProduct(productId, stock) {
        const modal = new bootstrap.Modal(document.getElementById('stockAdjustModal'));
        document.getElementById('adjust_product_id').value = productId;
        onProductSelectChange();
        modal.show();
    }

    function openQuickAddModal(id, name, stock, unit) {
        document.getElementById('quick_add_product_id').value = id;
        document.getElementById('quick_add_name').textContent = name;
        document.getElementById('quick_add_curr').textContent = stock + ' ' + unit;
        new bootstrap.Modal(document.getElementById('quickAddModal')).show();
    }

    function openQuickRemoveModal(id, name, stock, unit) {
        document.getElementById('quick_remove_product_id').value = id;
        document.getElementById('quick_remove_name').textContent = name;
        document.getElementById('quick_remove_curr').textContent = stock + ' ' + unit;
        document.getElementById('quick_remove_qty').max = stock;
        new bootstrap.Modal(document.getElementById('quickRemoveModal')).show();
    }
</script>
@endpush
@endsection
