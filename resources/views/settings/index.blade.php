@extends('layouts.app')

@section('title', 'System & POS Settings')

@section('content')
<div class="container-fluid p-0" style="max-width: 1080px;">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Store & POS System Settings</h3>
            <p class="text-muted mb-0">Configure business identity, receipts, invoice numbering, default tax rates, and POS terminal behavior.</p>
        </div>
        <div>
            <button type="submit" form="settingsForm" class="btn btn-primary px-4 py-2 fw-bold shadow-xs">
                <i class="bi bi-floppy2-fill me-1.5"></i> Save Settings
            </button>
        </div>
    </div>

    <!-- Live Store Brand Preview Banner -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3.5">
                <div class="p-2 rounded-3 bg-light border d-flex align-items-center justify-content-center" style="width: 72px; height: 72px;">
                    <img id="storeLogoPreview"
                         src="{{ asset($settings['store_logo'] ?? 'images/store-logo.svg') }}"
                         alt="Store Logo"
                         class="img-fluid object-fit-contain"
                         style="max-height: 56px; max-width: 56px;">
                </div>
                <div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill small mb-1">
                        Active Store Profile
                    </span>
                    <h4 class="fw-bold text-dark mb-0" id="liveStoreNameDisplay">
                        {{ $settings['store_name'] ?? 'OmniPOS Superstore' }}
                    </h4>
                    <p class="text-muted small mb-0">
                        <span id="liveStorePhoneDisplay"><i class="bi bi-telephone me-1"></i>{{ $settings['store_phone'] ?? '+1 (555) 019-2831' }}</span> &bull;
                        <span id="liveStoreEmailDisplay"><i class="bi bi-envelope me-1"></i>{{ $settings['store_email'] ?? 'contact@omnipos.com' }}</span> &bull;
                        <span><i class="bi bi-currency-exchange me-1"></i>Currency: <strong>{{ $settings['currency_symbol'] ?? '$' }}</strong></span>
                    </p>
                </div>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-light text-dark border p-2 text-center rounded-3">
                    <span class="text-muted d-block small" style="font-size: 0.7rem;">DEFAULT RECEIPT</span>
                    <strong class="text-uppercase text-primary">{{ $settings['receipt_size'] ?? '80mm' }}</strong>
                </span>
                <span class="badge bg-light text-dark border p-2 text-center rounded-3">
                    <span class="text-muted d-block small" style="font-size: 0.7rem;">STORE TAX</span>
                    <strong class="text-success">{{ $settings['store_tax'] ?? '10' }}%</strong>
                </span>
                <span class="badge bg-light text-dark border p-2 text-center rounded-3">
                    <span class="text-muted d-block small" style="font-size: 0.7rem;">INVOICE PREFIX</span>
                    <strong class="font-monospace text-dark">{{ $settings['invoice_prefix'] ?? 'INV-' }}</strong>
                </span>
            </div>
        </div>
    </div>

    <!-- Main Settings Form -->
    <form id="settingsForm" action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row g-4">
            <!-- LEFT COLUMN: STORE SETTINGS -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white">
                    <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-2.5">
                        <div class="rounded-3 bg-primary-subtle text-primary p-2 fs-5">
                            <i class="bi bi-shop"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">Store Settings</h5>
                            <small class="text-muted">General business identity and contact details</small>
                        </div>
                    </div>

                    <!-- Store Name -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Store Name <span class="text-danger">*</span></label>
                        <input type="text"
                               name="settings[store_name]"
                               id="store_name_input"
                               class="form-control"
                               value="{{ old('settings.store_name', $settings['store_name'] ?? 'OmniPOS Superstore') }}"
                               required
                               placeholder="e.g. Metro Retail Superstore">
                    </div>

                    <!-- Logo Upload -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Store Logo</label>
                        <div class="input-group">
                            <input type="file"
                                   name="store_logo_file"
                                   id="store_logo_file"
                                   class="form-control"
                                   accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                   onchange="previewUploadedLogo(this)">
                        </div>
                        <small class="text-muted">Recommended: Transparent PNG, SVG, or WebP. Max 2MB.</small>
                    </div>

                    <!-- Address -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Store Address</label>
                        <textarea name="settings[store_address]"
                                  class="form-control"
                                  rows="2"
                                  placeholder="e.g. 100 Downtown Boulevard, Suite 400, Metropolis">{{ old('settings.store_address', $settings['store_address'] ?? '100 Downtown Boulevard, Metropolis') }}</textarea>
                    </div>

                    <!-- Phone & Email -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Phone Number</label>
                            <input type="text"
                                   name="settings[store_phone]"
                                   id="store_phone_input"
                                   class="form-control"
                                   value="{{ old('settings.store_phone', $settings['store_phone'] ?? '+1 (555) 019-2831') }}"
                                   placeholder="+1 (555) 019-2831">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Email Address</label>
                            <input type="email"
                                   name="settings[store_email]"
                                   id="store_email_input"
                                   class="form-control"
                                   value="{{ old('settings.store_email', $settings['store_email'] ?? 'contact@omnipos.com') }}"
                                   placeholder="contact@omnipos.com">
                        </div>
                    </div>

                    <!-- Website -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Website URL</label>
                        <input type="text"
                               name="settings[store_website]"
                               class="form-control"
                               value="{{ old('settings.store_website', $settings['store_website'] ?? 'www.omnipos.com') }}"
                               placeholder="e.g. www.omnipos.com">
                    </div>

                    <!-- Currency, Store Tax & Invoice Prefix -->
                    <div class="row g-3">
                        <div class="col-4">
                            <label class="form-label fw-semibold text-dark">Currency <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="settings[currency_symbol]"
                                   class="form-control fw-bold font-monospace text-center"
                                   value="{{ old('settings.currency_symbol', $settings['currency_symbol'] ?? '$') }}"
                                   required
                                   maxlength="10"
                                   placeholder="$">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold text-dark">Store Tax (%)</label>
                            <input type="number"
                                   step="0.1"
                                   name="settings[store_tax]"
                                   class="form-control text-center"
                                   min="0"
                                   max="100"
                                   value="{{ old('settings.store_tax', $settings['store_tax'] ?? '10') }}"
                                   placeholder="10">
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold text-dark">Invoice Prefix <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="settings[invoice_prefix]"
                                   class="form-control font-monospace text-center fw-bold"
                                   value="{{ old('settings.invoice_prefix', $settings['invoice_prefix'] ?? 'INV-') }}"
                                   required
                                   maxlength="20"
                                   placeholder="INV-">
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: POS TERMINAL SETTINGS -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white">
                    <div class="d-flex align-items-center gap-2 mb-3 border-bottom pb-2.5">
                        <div class="rounded-3 bg-success-subtle text-success p-2 fs-5">
                            <i class="bi bi-cart4"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-0">POS Terminal Settings</h5>
                            <small class="text-muted">Checkout defaults, receipt format, and workflow toggles</small>
                        </div>
                    </div>

                    <!-- Default Tax & Default Discount -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Default Tax Rate (%)</label>
                            <div class="input-group">
                                <input type="number"
                                       step="0.1"
                                       name="settings[pos_default_tax]"
                                       class="form-control"
                                       min="0"
                                       max="100"
                                       value="{{ old('settings.pos_default_tax', $settings['pos_default_tax'] ?? '0') }}"
                                       placeholder="0">
                                <span class="input-group-text bg-light">%</span>
                            </div>
                            <small class="text-muted">Pre-filled on checkout cart.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Default Discount (%)</label>
                            <div class="input-group">
                                <input type="number"
                                       step="0.5"
                                       name="settings[pos_default_discount]"
                                       class="form-control"
                                       min="0"
                                       max="100"
                                       value="{{ old('settings.pos_default_discount', $settings['pos_default_discount'] ?? '0') }}"
                                       placeholder="0">
                                <span class="input-group-text bg-light">%</span>
                            </div>
                            <small class="text-muted">Default order discount rate.</small>
                        </div>
                    </div>

                    <!-- Receipt Size Selection -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Default Receipt Size</label>
                        <div class="row g-2">
                            @php
                                $currentSize = old('settings.receipt_size', $settings['receipt_size'] ?? '80mm');
                            @endphp
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="settings[receipt_size]" id="size_58mm" value="58mm" {{ $currentSize === '58mm' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary w-100 py-2.5 text-center rounded-3" for="size_58mm">
                                    <i class="bi bi-receipt d-block fs-5 mb-1"></i>
                                    <div class="fw-bold small">58mm</div>
                                    <span style="font-size: 0.68rem;" class="text-muted">Compact POS</span>
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="settings[receipt_size]" id="size_80mm" value="80mm" {{ $currentSize === '80mm' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary w-100 py-2.5 text-center rounded-3" for="size_80mm">
                                    <i class="bi bi-printer d-block fs-5 mb-1"></i>
                                    <div class="fw-bold small">80mm</div>
                                    <span style="font-size: 0.68rem;" class="text-muted">Standard POS</span>
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="settings[receipt_size]" id="size_a4" value="a4" {{ $currentSize === 'a4' ? 'checked' : '' }}>
                                <label class="btn btn-outline-secondary w-100 py-2.5 text-center rounded-3" for="size_a4">
                                    <i class="bi bi-file-earmark-text d-block fs-5 mb-1"></i>
                                    <div class="fw-bold small">A4</div>
                                    <span style="font-size: 0.68rem;" class="text-muted">Full Invoice</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Workflow Feature Toggles -->
                    <label class="form-label fw-semibold text-dark mb-2">POS Workflow Features</label>
                    <div class="border rounded-3 p-3 bg-light mb-3">
                        <!-- Enable Barcode -->
                        <div class="form-check form-switch mb-2.5">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="settings[enable_barcode]"
                                   value="1"
                                   id="toggle_enable_barcode"
                                   {{ (!isset($settings['enable_barcode']) || $settings['enable_barcode'] == '1') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="toggle_enable_barcode">
                                <i class="bi bi-upc-scan text-primary me-1"></i> Enable Barcode Scanning
                            </label>
                            <div class="text-muted small">Show dedicated barcode scan input toolbar on POS terminal.</div>
                        </div>

                        <!-- Enable Customer Selection -->
                        <div class="form-check form-switch mb-2.5">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="settings[enable_customer]"
                                   value="1"
                                   id="toggle_enable_customer"
                                   {{ (!isset($settings['enable_customer']) || $settings['enable_customer'] == '1') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="toggle_enable_customer">
                                <i class="bi bi-person-lines-fill text-success me-1"></i> Enable Customer Selection
                            </label>
                            <div class="text-muted small">Allow cashiers to search, select, or quick-add customers in cart.</div>
                        </div>

                        <!-- Enable Sound Effects -->
                        <div class="form-check form-switch">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="settings[enable_sound]"
                                   value="1"
                                   id="toggle_enable_sound"
                                   {{ (!isset($settings['enable_sound']) || $settings['enable_sound'] == '1') ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold text-dark" for="toggle_enable_sound">
                                <i class="bi bi-volume-up-fill text-info me-1"></i> Enable Audio Sound Effects
                            </label>
                            <div class="text-muted small">Play crisp audio cues on item add, barcode scan, and checkout completion.</div>
                        </div>
                    </div>

                    <!-- Receipt Footer Note -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold text-dark">Receipt Footer Message</label>
                        <textarea name="settings[receipt_footer]"
                                  class="form-control"
                                  rows="2"
                                  placeholder="Thank you for shopping with us!">{{ old('settings.receipt_footer', $settings['receipt_footer'] ?? 'Thank you for your purchase! Returns accepted within 14 days with original receipt.') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- GOOGLE FIREBASE CLOUD INTEGRATION & SYNCHRONIZATION -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mt-4 bg-white">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border-bottom pb-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-warning-subtle text-warning p-2.5 fs-4">
                        <i class="bi bi-fire text-danger"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold text-dark mb-0">Google Firebase Cloud Synchronization</h5>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" id="fbStatusBadge">
                                <i class="bi bi-check-circle-fill me-1"></i> Connected
                            </span>
                        </div>
                        <small class="text-muted">Real-time cloud database storage for sales, products, and customers via Firestore</small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 px-3" onclick="testFirebaseConnection()">
                        <i class="bi bi-activity me-1"></i> Test Connection
                    </button>
                    <button type="button" class="btn btn-warning btn-sm rounded-3 px-3 text-dark fw-bold" onclick="syncDataToFirebase()">
                        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Sync All Data to Firebase
                    </button>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small fw-semibold">PROJECT ID</div>
                        <div class="fw-bold text-dark font-monospace text-truncate">{{ config('firebase.project_id') }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small fw-semibold">AUTH DOMAIN</div>
                        <div class="fw-bold text-dark font-monospace text-truncate">{{ config('firebase.auth_domain') }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small fw-semibold">STORAGE BUCKET</div>
                        <div class="fw-bold text-dark font-monospace text-truncate">{{ config('firebase.storage_bucket') }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="text-muted small fw-semibold">CLOUD DATABASE</div>
                        <div class="fw-bold text-success"><i class="bi bi-database-check me-1"></i> Cloud Firestore (Active)</div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-light border mt-3 mb-0 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 p-2.5 rounded-3">
                <div class="small text-muted">
                    <i class="bi bi-info-circle-fill text-primary me-1"></i>
                    All completed POS transactions and products automatically persist to Firebase Firestore collections (<code>sales</code>, <code>products</code>, <code>customers</code>).
                </div>
                <span class="small font-monospace text-muted" id="fbLastSyncTime">Auto-sync: Active</span>
            </div>
        </div>

        <!-- Sticky Footer Save Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mt-4 bg-white d-flex flex-row justify-content-between align-items-center">
            <span class="text-muted small">
                <i class="bi bi-info-circle me-1 text-primary"></i> Changes apply immediately to POS checkout, receipts, invoice numbering, and layouts.
            </span>
            <button type="submit" class="btn btn-primary px-4 fw-bold">
                <i class="bi bi-floppy2-fill me-1.5"></i> Save Settings
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function previewUploadedLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('storeLogoPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Live preview text updates
document.getElementById('store_name_input')?.addEventListener('input', function() {
    document.getElementById('liveStoreNameDisplay').innerText = this.value || 'Store Name';
});
document.getElementById('store_phone_input')?.addEventListener('input', function() {
    document.getElementById('liveStorePhoneDisplay').innerHTML = `<i class="bi bi-telephone me-1"></i>${this.value || '—'}`;
});
document.getElementById('store_email_input')?.addEventListener('input', function() {
    document.getElementById('liveStoreEmailDisplay').innerHTML = `<i class="bi bi-envelope me-1"></i>${this.value || '—'}`;
});

// Firebase Cloud Sync Functions
async function testFirebaseConnection() {
    Swal.fire({
        title: 'Testing Firebase Connection...',
        text: 'Checking connection to Google Firebase Project (pos-management-88866)...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        let result = null;
        if (window.FirebaseSync && typeof window.FirebaseSync.testConnection === 'function') {
            result = await window.FirebaseSync.testConnection();
        } else {
            const res = await fetch('{{ route("firebase.status") }}');
            result = await res.json();
        }

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Firebase Connected!',
                text: result.message || 'Successfully connected to Google Firebase Firestore!',
                confirmButtonColor: '#4338ca'
            });
            const badge = document.getElementById('fbStatusBadge');
            if (badge) {
                badge.className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill';
                badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Connected';
            }
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Firebase Connection Notice',
                text: result.message || 'Could not reach Firebase server.',
                confirmButtonColor: '#4338ca'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Connection Error',
            text: err.message,
            confirmButtonColor: '#4338ca'
        });
    }
}

async function syncDataToFirebase() {
    const confirm = await Swal.fire({
        title: 'Sync All Data to Firebase?',
        text: 'This will store all products, sales records, and customers into Google Firebase Firestore collections.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Sync Now',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#f59e0b'
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'Synchronizing with Firebase...',
        text: 'Uploading records into Firestore database...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        let counts = {};
        if (window.FirebaseSync && typeof window.FirebaseSync.syncAllFromBackend === 'function') {
            counts = await window.FirebaseSync.syncAllFromBackend();
        } else {
            const res = await fetch('{{ route("firebase.sync") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });
            const data = await res.json();
            counts = data.counts || {};
        }

        const syncElem = document.getElementById('fbLastSyncTime');
        if (syncElem) {
            syncElem.innerText = 'Last Synced: ' + new Date().toLocaleTimeString();
        }

        Swal.fire({
            icon: 'success',
            title: 'Firebase Sync Complete!',
            html: `Successfully stored records in Google Firebase Firestore:<br><br>
                   <div class="text-start bg-light p-3 rounded font-monospace small">
                     &bull; Products Synced: <b>${counts.products || 0}</b><br>
                     &bull; Sales Synced: <b>${counts.sales || 0}</b><br>
                     &bull; Customers Synced: <b>${counts.customers || 0}</b>
                   </div>`,
            confirmButtonColor: '#4338ca'
        });
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Sync Failed',
            text: err.message,
            confirmButtonColor: '#4338ca'
        });
    }
}
</script>
@endpush
@endsection
