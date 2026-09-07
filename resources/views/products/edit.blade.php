@extends('layouts.app')

@section('title', 'Edit Product: ' . $product->name)

@section('content')
<div class="container-fluid p-0" style="max-width: 1000px;">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Products
                </a>
                <span class="text-muted">/</span>
                <span class="text-muted small fw-semibold">Edit #{{ $product->id }}</span>
            </div>
            <h3 class="fw-bold text-dark mb-0">Edit Product: {{ $product->name }}</h3>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('products.show', $product) }}" class="btn btn-outline-dark rounded-pill px-3">
                <i class="bi bi-eye me-1"></i> View Details
            </a>
            <button type="button" class="btn btn-outline-danger rounded-pill px-3" onclick="confirmDeleteProduct({{ $product->id }}, '{{ addslashes($product->name) }}')">
                <i class="bi bi-trash me-1"></i> Delete
            </button>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary rounded-pill px-3">
                <i class="bi bi-x-lg me-1"></i> Cancel
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <h6 class="fw-bold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i> Please fix the following errors:</h6>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Left Side: Basic Info & Pricing -->
            <div class="col-lg-8">
                <!-- General Info Card -->
                <x-card class="mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle me-2 text-primary"></i> Basic Details</h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror" value="{{ old('name', $product->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">SKU (Stock Keeping Unit) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" name="sku" id="skuInput" class="form-control font-monospace @error('sku') is-invalid @enderror" value="{{ old('sku', $product->sku) }}" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="generateSku()" title="Regenerate SKU">
                                    <i class="bi bi-arrow-repeat"></i> Auto
                                </button>
                            </div>
                            @error('sku') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Barcode (EAN / UPC / Code128)</label>
                            <div class="input-group">
                                <input type="text" name="barcode" id="barcodeInput" class="form-control font-monospace @error('barcode') is-invalid @enderror" value="{{ old('barcode', $product->barcode) }}">
                                <button type="button" class="btn btn-outline-secondary" onclick="generateBarcode()" title="Regenerate Barcode">
                                    <i class="bi bi-upc-scan"></i> Auto
                                </button>
                            </div>
                            @error('barcode') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                        </div>
                    </div>
                </x-card>

                <!-- Pricing & Financials Card -->
                <x-card class="mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-currency-dollar me-2 text-primary"></i> Pricing Information</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Purchase / Cost Price ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">$</span>
                                <input type="number" step="0.01" min="0" name="cost_price" id="costPriceInput" class="form-control @error('cost_price') is-invalid @enderror" value="{{ old('cost_price', $product->cost_price) }}" required oninput="calculateMargin()">
                            </div>
                            <small class="text-muted">Unit purchase cost</small>
                            @error('cost_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Retail Selling Price ($) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">$</span>
                                <input type="number" step="0.01" min="0" name="selling_price" id="sellingPriceInput" class="form-control @error('selling_price') is-invalid @enderror" value="{{ old('selling_price', $product->selling_price) }}" required oninput="calculateMargin()">
                            </div>
                            <small class="text-muted" id="marginDisplay">Margin: $0.00 (0%)</small>
                            @error('selling_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Wholesale Price ($)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">$</span>
                                <input type="number" step="0.01" min="0" name="wholesale_price" class="form-control @error('wholesale_price') is-invalid @enderror" value="{{ old('wholesale_price', $product->wholesale_price ?? '0.00') }}">
                            </div>
                            <small class="text-muted">Optional bulk price</small>
                            @error('wholesale_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </x-card>

                <!-- Inventory & Stock Management -->
                <x-card>
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-box-seam me-2 text-primary"></i> Inventory Tracking</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="stock_quantity" class="form-control @error('stock_quantity') is-invalid @enderror" value="{{ old('stock_quantity', $product->stock_quantity) }}" required>
                            <small class="text-muted">Current inventory on-hand</small>
                            @error('stock_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Minimum / Alert Stock <span class="text-danger">*</span></label>
                            <input type="number" min="0" name="alert_quantity" class="form-control @error('alert_quantity') is-invalid @enderror" value="{{ old('alert_quantity', $product->alert_quantity) }}" required>
                            <small class="text-muted">Triggers low stock alert</small>
                            @error('alert_quantity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Unit of Measure <span class="text-danger">*</span></label>
                            <input type="text" name="unit" class="form-control @error('unit') is-invalid @enderror" value="{{ old('unit', $product->unit) }}" required>
                            <small class="text-muted">e.g. pcs, box, bottle</small>
                            @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </x-card>
            </div>

            <!-- Right Side: Organization, Supplier & Photo -->
            <div class="col-lg-4">
                <!-- Product Image Upload -->
                <x-card class="mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-image me-2 text-primary"></i> Product Image</h5>
                    <div class="text-center p-3 border rounded-3 bg-light mb-3">
                        <img id="imagePreview" src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-fluid rounded-3 mb-2" style="max-height: 160px; object-fit: contain;">
                        <div class="small text-muted" id="previewFilename">{{ $product->image ? basename($product->image) : 'No custom image' }}</div>
                    </div>
                    <input type="file" name="image" id="imageInput" class="form-control @error('image') is-invalid @enderror" accept="image/*" onchange="previewProductImage(this)">
                    <small class="text-muted d-block mt-1">PNG, JPG, or WebP up to 2MB to replace image.</small>
                    @error('image') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </x-card>

                <!-- Classification & Relations -->
                <x-card class="mb-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-grid me-2 text-primary"></i> Organization</h5>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror">
                            <option value="">Select Category</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Brand / Manufacturer</label>
                        <select name="brand_id" class="form-select @error('brand_id') is-invalid @enderror">
                            <option value="">Select Brand</option>
                            @foreach ($brands as $b)
                                <option value="{{ $b->id }}" {{ old('brand_id', $product->brand_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Default Supplier</label>
                        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror">
                            <option value="">Select Supplier</option>
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}" {{ old('supplier_id', $product->supplier_id) == $sup->id ? 'selected' : '' }}>{{ $sup->name }} ({{ $sup->company_name ?? 'Individual' }})</option>
                            @endforeach
                        </select>
                        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="form-check form-switch mt-3 pt-2 border-top">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editProductActive" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="editProductActive">Available for sale in POS</label>
                    </div>
                </x-card>

                <!-- Submit Button Block -->
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
                        <i class="bi bi-check-circle me-1"></i> Update Product
                    </button>
                    <a href="{{ route('products.index') }}" class="btn btn-light rounded-pill">Cancel</a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function previewProductImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('previewFilename').innerText = input.files[0].name;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    async function generateSku() {
        try {
            const res = await fetchJson('{{ route("products.generate-sku") }}');
            if (res.success) {
                document.getElementById('skuInput').value = res.sku;
                Toast.fire({ icon: 'success', title: 'New SKU: ' + res.sku });
            }
        } catch (e) {
            const random = 'PRD-' + Math.random().toString(36).substring(2, 8).toUpperCase();
            document.getElementById('skuInput').value = random;
        }
    }

    async function generateBarcode() {
        try {
            const res = await fetchJson('{{ route("products.generate-barcode") }}');
            if (res.success) {
                document.getElementById('barcodeInput').value = res.barcode;
                Toast.fire({ icon: 'success', title: 'New Barcode: ' + res.barcode });
            }
        } catch (e) {
            const random = '200' + Math.floor(100000000 + Math.random() * 900000000);
            document.getElementById('barcodeInput').value = random;
        }
    }

    function calculateMargin() {
        const cost = parseFloat(document.getElementById('costPriceInput').value) || 0;
        const sell = parseFloat(document.getElementById('sellingPriceInput').value) || 0;
        const profit = sell - cost;
        const percent = cost > 0 ? ((profit / cost) * 100).toFixed(1) : 0;
        
        const display = document.getElementById('marginDisplay');
        if (profit >= 0) {
            display.innerHTML = `<span class="text-success fw-bold">Margin: +$${profit.toFixed(2)} (${percent}%)</span>`;
        } else {
            display.innerHTML = `<span class="text-danger fw-bold">Loss: -$${Math.abs(profit).toFixed(2)} (${percent}%)</span>`;
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
                    const res = await fetchJson(`{{ route('products.destroy', $product) }}`, { method: 'DELETE' });
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

    document.addEventListener('DOMContentLoaded', calculateMargin);
</script>
@endpush
