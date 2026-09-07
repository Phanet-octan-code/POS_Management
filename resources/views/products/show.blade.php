@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="container-fluid p-0">
    <!-- Header with Action Buttons -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Products
                </a>
                <span class="text-muted">/</span>
                <span class="text-muted small fw-semibold">{{ $product->category?->name ?? 'Uncategorized' }}</span>
            </div>
            <h3 class="fw-bold text-dark mb-0">{{ $product->name }}</h3>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-3">
                <i class="bi bi-printer me-1"></i> Print Details
            </button>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-primary rounded-pill px-3 shadow-sm">
                <i class="bi bi-pencil-square me-1"></i> Edit Product
            </a>
            <button class="btn btn-outline-danger rounded-pill px-3" onclick="confirmDeleteProduct({{ $product->id }}, '{{ addslashes($product->name) }}')">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Image, Barcode & Fast Stats -->
        <div class="col-lg-4">
            <!-- Product Photo & Status -->
            <x-card class="mb-4 text-center">
                <div class="position-relative d-inline-block mb-3">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-fluid rounded-4 border p-2 shadow-sm bg-white" style="max-height: 240px; width: 100%; object-fit: contain;">
                    <div class="position-absolute top-0 end-0 m-2">
                        <button type="button" 
                                class="btn btn-sm border-0 p-0"
                                onclick="toggleProductStatus({{ $product->id }})"
                                id="status-toggle-btn"
                                title="Click to toggle status">
                            <span class="badge {{ $product->is_active ? 'bg-success text-white' : 'bg-secondary text-white' }} px-3 py-1.5 rounded-pill shadow-sm">
                                <i class="bi {{ $product->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }} me-1"></i>
                                <span id="status-toggle-text">{{ $product->is_active ? 'Active' : 'Inactive' }}</span>
                            </span>
                        </button>
                    </div>
                </div>

                <h5 class="fw-bold text-dark mb-1">{{ $product->name }}</h5>
                <p class="text-muted small mb-3">SKU: <code class="fw-bold">{{ $product->sku }}</code></p>

                <!-- Barcode Card -->
                <div class="bg-light rounded-3 p-3 border">
                    <div class="small fw-semibold text-muted text-uppercase mb-2" style="letter-spacing: 0.05em;">Scannable Barcode</div>
                    @if($product->barcode)
                        <svg id="barcodeSvg" class="img-fluid mb-2"></svg>
                        <div class="fw-bold font-monospace text-dark">{{ $product->barcode }}</div>
                    @else
                        <div class="text-muted small py-2">No barcode registered</div>
                    @endif
                </div>
            </x-card>

            <!-- Categorization Details -->
            <x-card>
                <h6 class="fw-bold text-dark mb-3 text-uppercase small" style="letter-spacing: 0.05em;">Classification</h6>
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Category:</span>
                        <span class="fw-bold text-dark">{{ $product->category?->name ?? 'None' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Brand:</span>
                        <span class="fw-bold text-dark">{{ $product->brand?->name ?? 'None' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Supplier:</span>
                        <span class="fw-bold text-dark">{{ $product->supplier?->name ?? 'None' }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Unit of Measure:</span>
                        <span class="fw-bold text-dark">{{ $product->unit }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0 py-2">
                        <span class="text-muted">Created:</span>
                        <span class="text-secondary">{{ $product->created_at->format('M d, Y H:i') }}</span>
                    </li>
                </ul>
            </x-card>
        </div>

        <!-- Right Column: Financials, Stock & Audit Movements -->
        <div class="col-lg-8">
            <!-- Financial Overview Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Purchase / Cost</small>
                        <h3 class="fw-bold text-dark mt-2 mb-0">${{ number_format($product->cost_price, 2) }}</h3>
                        <small class="text-muted">Per {{ $product->unit }}</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100 border-start border-primary border-4">
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Retail Selling Price</small>
                        <h3 class="fw-bold text-primary mt-2 mb-0">${{ number_format($product->selling_price, 2) }}</h3>
                        @php
                            $profit = $product->selling_price - $product->cost_price;
                            $margin = $product->cost_price > 0 ? round(($profit / $product->cost_price) * 100, 1) : 0;
                        @endphp
                        <small class="text-success fw-semibold"><i class="bi bi-graph-up-arrow me-1"></i>+${{ number_format($profit, 2) }} ({{ $margin }}%)</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 rounded-4 shadow-sm p-3 bg-white h-100">
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Wholesale Price</small>
                        <h3 class="fw-bold text-dark mt-2 mb-0">${{ number_format($product->wholesale_price ?? 0, 2) }}</h3>
                        <small class="text-muted">Bulk / B2B sales</small>
                    </div>
                </div>
            </div>

            <!-- Stock & Inventory Status -->
            <x-card class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="bi bi-boxes me-2 text-primary"></i> Current Stock Status</h5>
                    <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                        <i class="bi bi-sliders me-1"></i> Stock Adjustments
                    </a>
                </div>
                <div class="row g-3 align-items-center p-3 bg-light rounded-3">
                    <div class="col-md-4 text-center border-end">
                        <div class="text-muted small text-uppercase fw-semibold">Current On-Hand</div>
                        <div class="fs-2 fw-bold text-dark mt-1">{{ $product->current_stock }} <small class="fs-6 text-muted">{{ $product->unit }}</small></div>
                        @if($product->current_stock <= 0)
                            <span class="badge bg-danger text-white rounded-pill px-3">Out of Stock</span>
                        @elseif($product->isLowStock())
                            <span class="badge bg-warning text-dark rounded-pill px-3">Low Stock Warning</span>
                        @else
                            <span class="badge bg-success text-white rounded-pill px-3">In Stock</span>
                        @endif
                    </div>
                    <div class="col-md-4 text-center border-end">
                        <div class="text-muted small text-uppercase fw-semibold">Alert Threshold</div>
                        <div class="fs-2 fw-bold text-secondary mt-1">{{ $product->alert_quantity }} <small class="fs-6 text-muted">{{ $product->unit }}</small></div>
                        <small class="text-muted">Low stock triggers alert</small>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="text-muted small text-uppercase fw-semibold">Total Stock Valuation</div>
                        <div class="fs-2 fw-bold text-primary mt-1">${{ number_format($product->current_stock * $product->cost_price, 2) }}</div>
                        <small class="text-muted">Based on Cost Price</small>
                    </div>
                </div>

                @if($product->description)
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold text-dark text-uppercase small">Description</h6>
                        <p class="text-muted mb-0">{{ $product->description }}</p>
                    </div>
                @endif
            </x-card>

            <!-- Multi-Location Shelf Stocks -->
            @if($product->stocks->isNotEmpty())
                <x-card class="mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-geo-alt me-2 text-primary"></i> Stock By Location & Batches</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Location</th>
                                    <th>Batch #</th>
                                    <th>Expiry Date</th>
                                    <th class="text-end">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product->stocks as $stk)
                                    <tr>
                                        <td class="fw-semibold">{{ $stk->location }}</td>
                                        <td><code>{{ $stk->batch_number ?? 'N/A' }}</code></td>
                                        <td>{{ $stk->expiry_date ? $stk->expiry_date->format('M d, Y') : 'N/A' }}</td>
                                        <td class="text-end fw-bold">{{ $stk->quantity }} {{ $product->unit }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif

            <!-- Recent Stock Movements / Audit Trail -->
            <x-card>
                <h5 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-2 text-primary"></i> Recent Stock Movement History</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Change</th>
                                <th>Balance</th>
                                <th>Reason / Notes</th>
                                <th>Operator</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->stockMovements as $mov)
                                <tr>
                                    <td class="small text-muted">{{ $mov->created_at->format('M d, Y H:i') }}</td>
                                    <td>
                                        <span class="badge {{ $mov->quantity >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} text-uppercase">
                                            {{ $mov->type }}
                                        </span>
                                    </td>
                                    <td class="fw-bold {{ $mov->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $mov->quantity >= 0 ? '+' : '' }}{{ $mov->quantity }}
                                    </td>
                                    <td class="small text-muted">{{ $mov->stock_after }} {{ $product->unit }}</td>
                                    <td class="small text-dark">{{ $mov->reason ?? 'Transaction adjustment' }}</td>
                                    <td class="small text-muted">{{ $mov->user?->name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-3 text-muted small">No stock movements recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        @if($product->barcode)
            try {
                JsBarcode("#barcodeSvg", "{{ $product->barcode }}", {
                    format: "CODE128",
                    width: 2,
                    height: 55,
                    displayValue: false,
                    lineColor: "#0f172a"
                });
            } catch (e) {
                console.warn('Barcode render note:', e);
            }
        @endif
    });

    async function toggleProductStatus(id) {
        try {
            const res = await fetchJson(`/products/${id}/status`, { method: 'PATCH' });
            if (res.success) {
                const btn = document.getElementById('status-toggle-btn');
                if (res.is_active) {
                    btn.innerHTML = `<span class="badge bg-success text-white px-3 py-1.5 rounded-pill shadow-sm"><i class="bi bi-check-circle-fill me-1"></i>Active</span>`;
                } else {
                    btn.innerHTML = `<span class="badge bg-secondary text-white px-3 py-1.5 rounded-pill shadow-sm"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>`;
                }
                Toast.fire({ icon: 'success', title: res.message });
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to update product status.', 'error');
        }
    }

    function confirmDeleteProduct(id, name) {
        Swal.fire({
            title: 'Delete Product?',
            text: `Are you sure you want to delete '${name}'?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete product'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetchJson(`/products/${id}`, { method: 'DELETE' });
                    if (res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: res.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = "{{ route('products.index') }}";
                        });
                    } else {
                        Swal.fire('Error', res.message || 'Failed to delete product.', 'error');
                    }
                } catch (err) {
                    Swal.fire('Error', 'Failed to delete product.', 'error');
                }
            }
        });
    }
</script>
@endpush
