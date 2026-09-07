@extends('layouts.app')

@section('title', 'Products')

@section('content')
<div class="container-fluid p-0">
    <!-- Header with Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1"><i class="bi bi-box-seam me-2 text-primary"></i> Product Management</h3>
            <p class="text-muted mb-0">Manage catalog items, pricing, inventory stock, barcodes, and suppliers.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-tags me-1"></i> Categories
            </a>
            <a href="{{ route('brands.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-award me-1"></i> Brands
            </a>
            <a href="{{ route('products.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> Add Product
            </a>
        </div>
    </div>

    <!-- KPI Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-4">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Total Items</small>
                        <h4 class="fw-bold text-dark mb-0">{{ $totalProducts }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-success-subtle text-success p-3 fs-4">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">In Stock</small>
                        <h4 class="fw-bold text-success mb-0">{{ $totalProducts - $outOfStockCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning-subtle text-warning p-3 fs-4">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Low Stock</small>
                        <h4 class="fw-bold text-warning mb-0">{{ $lowStockCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-danger-subtle text-danger p-3 fs-4">
                        <i class="bi bi-x-octagon"></i>
                    </div>
                    <div>
                        <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.72rem;">Out of Stock</small>
                        <h4 class="fw-bold text-danger mb-0">{{ $outOfStockCount }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <x-card class="mb-4">
        <form method="GET" action="{{ route('products.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4 col-lg-3">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, SKU, barcode..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3 col-lg-2">
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <select name="brand_id" class="form-select">
                    <option value="">All Brands</option>
                    @foreach ($brands as $b)
                        <option value="{{ $b->id }}" {{ request('brand_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-lg-2">
                <select name="stock_status" class="form-select">
                    <option value="">All Stock Levels</option>
                    <option value="in" {{ request('stock_status') === 'in' ? 'selected' : '' }}>In Stock (>0)</option>
                    <option value="low" {{ request('stock_status') === 'low' ? 'selected' : '' }}>Low Stock Warning</option>
                    <option value="out" {{ request('stock_status') === 'out' ? 'selected' : '' }}>Out of Stock (0)</option>
                </select>
            </div>
            <div class="col-md-3 col-lg-1">
                <select name="status" class="form-select">
                    <option value="">Status</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-dark w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                @if(request()->hasAny(['search', 'category_id', 'brand_id', 'stock_status', 'status']))
                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary" title="Reset Filters"><i class="bi bi-arrow-counterclockwise"></i></a>
                @endif
            </div>
        </form>
    </x-card>

    <!-- Product Catalog Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th style="width: 50px;">Photo</th>
                        <th>Product Details</th>
                        <th>Classification</th>
                        <th>Cost Price</th>
                        <th>Selling Price</th>
                        <th>Wholesale</th>
                        <th>Stock Level</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    @forelse ($products as $prod)
                        <tr id="product-row-{{ $prod->id }}">
                            <td>
                                <div class="rounded-3 border bg-light p-1 d-flex align-items-center justify-content-center" style="width: 46px; height: 46px;">
                                    <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}" class="img-fluid rounded" style="max-height: 38px; object-fit: contain;">
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold text-dark">
                                    <a href="{{ route('products.show', $prod) }}" class="text-dark text-decoration-none hover-primary">
                                        {{ $prod->name }}
                                    </a>
                                </div>
                                <div class="d-flex align-items-center gap-2 mt-1 small">
                                    <span class="badge bg-light text-dark border font-monospace">{{ $prod->sku }}</span>
                                    @if($prod->barcode)
                                        <span class="text-muted"><i class="bi bi-upc-scan me-1"></i>{{ $prod->barcode }}</span>
                                    @endif
                                    <span class="text-muted">({{ $prod->unit }})</span>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        {{ $prod->category?->name ?? 'Uncategorized' }}
                                    </span>
                                </div>
                                @if($prod->brand)
                                    <small class="text-muted d-block mt-1"><i class="bi bi-award me-1"></i>{{ $prod->brand->name }}</small>
                                @endif
                                @if($prod->supplier)
                                    <small class="text-muted d-block" style="font-size: 0.72rem;"><i class="bi bi-truck me-1"></i>{{ $prod->supplier->name }}</small>
                                @endif
                            </td>
                            <td class="text-muted">${{ number_format($prod->cost_price, 2) }}</td>
                            <td>
                                <div class="fw-bold text-primary">${{ number_format($prod->selling_price, 2) }}</div>
                                @php
                                    $profit = $prod->selling_price - $prod->cost_price;
                                    $margin = $prod->cost_price > 0 ? round(($profit / $prod->cost_price) * 100, 0) : 0;
                                @endphp
                                <small class="text-success fw-semibold">+{{ $margin }}%</small>
                            </td>
                            <td class="text-secondary">${{ number_format($prod->wholesale_price ?? 0, 2) }}</td>
                            <td>
                                @if ($prod->stock_quantity <= 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill">
                                        <i class="bi bi-x-circle me-1"></i> Out of stock (0)
                                    </span>
                                @elseif ($prod->isLowStock())
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2.5 py-1.5 rounded-pill">
                                        <i class="bi bi-exclamation-circle me-1"></i> Low ({{ $prod->stock_quantity }} {{ $prod->unit }})
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill">
                                        <i class="bi bi-check2 me-1"></i> {{ $prod->stock_quantity }} {{ $prod->unit }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm badge-status-btn border-0 p-0 bg-transparent"
                                        onclick="toggleProductStatus({{ $prod->id }})"
                                        id="prod-status-btn-{{ $prod->id }}"
                                        title="Click to toggle status">
                                    <span class="badge {{ $prod->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }} px-3 py-1.5 rounded-pill">
                                        <i class="bi {{ $prod->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }} me-1"></i>
                                        <span id="prod-status-text-{{ $prod->id }}">{{ $prod->is_active ? 'Active' : 'Inactive' }}</span>
                                    </span>
                                </button>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-secondary" onclick="openProductQuickView({{ $prod->id }})" title="Quick View">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <a href="{{ route('products.edit', $prod) }}" class="btn btn-outline-primary" title="Edit Product">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button class="btn btn-outline-danger" onclick="confirmDeleteProduct({{ $prod->id }}, '{{ addslashes($prod->name) }}')" title="Delete Product">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                <h5>No products found</h5>
                                <p class="small mb-3">Try clearing or adjusting your search filters, or create a new product item.</p>
                                <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary rounded-pill px-3">
                                    <i class="bi bi-plus-circle me-1"></i> Add First Product
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $products->links() }}
            </div>
        @endif
    </x-card>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="productQuickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-dark" id="qvModalTitle">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="qvModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <a href="#" id="qvFullDetailsBtn" class="btn btn-outline-primary rounded-pill px-3">Full Page View</a>
                <a href="#" id="qvEditBtn" class="btn btn-primary rounded-pill px-4 fw-semibold">Edit Product</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
    async function toggleProductStatus(id) {
        try {
            const res = await fetchJson(`/products/${id}/status`, { method: 'PATCH' });
            if (res.success) {
                const btn = document.getElementById(`prod-status-btn-${id}`);
                if (res.is_active) {
                    btn.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1.5 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Active</span>`;
                } else {
                    btn.innerHTML = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1.5 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>`;
                }
                Toast.fire({ icon: 'success', title: res.message });
            }
        } catch (e) {
            Swal.fire('Error', 'Failed to toggle product status.', 'error');
        }
    }

    async function openProductQuickView(id) {
        const modal = new bootstrap.Modal(document.getElementById('productQuickViewModal'));
        modal.show();

        const body = document.getElementById('qvModalBody');
        body.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>`;

        try {
            const res = await fetchJson(`/products/${id}`);
            if (res.success) {
                const p = res.product;
                document.getElementById('qvModalTitle').innerText = p.name;
                document.getElementById('qvFullDetailsBtn').href = `/products/${p.id}`;
                document.getElementById('qvEditBtn').href = `/products/${p.id}/edit`;

                body.innerHTML = `
                    <div class="row g-3">
                        <div class="col-md-4 text-center">
                            <img src="${res.image_url}" alt="${p.name}" class="img-fluid rounded-3 border p-2 mb-3" style="max-height: 180px; object-fit: contain;">
                            <div class="bg-light p-2 rounded border">
                                <svg id="qvBarcodeSvg" class="img-fluid"></svg>
                                <div class="font-monospace small fw-bold mt-1">${p.barcode || 'No Barcode'}</div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h4 class="fw-bold text-dark mb-1">${p.name}</h4>
                            <p class="text-muted small mb-3">${p.description || 'No description provided.'}</p>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-sm-4 p-2 bg-light rounded text-center">
                                    <small class="text-muted d-block">Cost Price</small>
                                    <span class="fw-bold fs-5">$${parseFloat(p.cost_price).toFixed(2)}</span>
                                </div>
                                <div class="col-sm-4 p-2 bg-light rounded text-center border-start border-primary border-3">
                                    <small class="text-muted d-block">Selling Price</small>
                                    <span class="fw-bold fs-5 text-primary">$${parseFloat(p.selling_price).toFixed(2)}</span>
                                </div>
                                <div class="col-sm-4 p-2 bg-light rounded text-center">
                                    <small class="text-muted d-block">Margin</small>
                                    <span class="fw-bold fs-5 text-success">+$${res.margin} (${res.margin_percent}%)</span>
                                </div>
                            </div>

                            <ul class="list-group list-group-flush small">
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">SKU:</span>
                                    <code class="fw-bold">${p.sku}</code>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Category:</span>
                                    <span class="fw-bold">${p.category ? p.category.name : 'None'}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Brand:</span>
                                    <span class="fw-bold">${p.brand ? p.brand.name : 'None'}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Supplier:</span>
                                    <span class="fw-bold">${p.supplier ? p.supplier.name : 'None'}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between px-0">
                                    <span class="text-muted">Stock On Hand:</span>
                                    <span class="fw-bold ${p.stock_quantity <= p.alert_quantity ? 'text-danger' : 'text-success'}">
                                        ${p.stock_quantity} ${p.unit} (Alert: ${p.alert_quantity})
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                `;

                if (p.barcode) {
                    try {
                        JsBarcode("#qvBarcodeSvg", p.barcode, {
                            format: "CODE128",
                            width: 1.8,
                            height: 40,
                            displayValue: false
                        });
                    } catch(e) {}
                }
            }
        } catch (e) {
            body.innerHTML = `<div class="alert alert-danger">Failed to load product information.</div>`;
        }
    }

    function confirmDeleteProduct(id, name) {
        if (!id) {
            Swal.fire('Error', 'Invalid product ID.', 'error');
            return;
        }

        Swal.fire({
            title: 'Delete Product?',
            text: `Are you sure you want to delete '${name}'?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it!'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const res = await fetchJson(`/products/${id}`, { 
                        method: 'DELETE',
                        body: JSON.stringify({ id: id })
                    });
                    if (res.success) {
                        const row = document.getElementById(`product-row-${id}`);
                        if (row) row.remove();
                        Toast.fire({ icon: 'success', title: res.message });
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
