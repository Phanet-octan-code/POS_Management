@extends('layouts.pos')

@section('title', 'POS Sales Terminal')

@section('content')
<div class="row g-0 h-100">
    <!-- ======================================================== -->
    <!-- LEFT COLUMN: Barcode, Filters, Search & Product Grid     -->
    <!-- ======================================================== -->
    <div class="col-lg-7 col-xl-8 d-flex flex-column h-100 border-end bg-light">
        <!-- Top Controls: Barcode Scanner & Search Toolbar -->
        <div class="p-3 bg-white border-bottom shadow-xs">
            <div class="row g-2 align-items-center">
                @if ($posSettings['enable_barcode'])
                    <!-- Barcode Scanner Input (Auto-Add On Enter/Scan) -->
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white border-primary">
                                <i class="bi bi-upc-scan"></i>
                            </span>
                            <input type="text"
                                   id="barcodeScannerInput"
                                   class="form-control border-primary bg-light fw-bold font-monospace"
                                   placeholder="Scan / Enter Barcode (Auto-Add)..."
                                   autocomplete="off"
                                   autofocus>
                            <button class="btn btn-outline-primary" type="button" onclick="triggerBarcodeScan()" title="Search & Add Barcode">
                                <i class="bi bi-arrow-return-left"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Product Name / SKU Search -->
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text"
                                   id="posSearchInput"
                                   class="form-control border-start-0 bg-light"
                                   placeholder="Search name or SKU...">
                        </div>
                    </div>
                @else
                    <!-- Product Name / SKU Search (Full Width when barcode scanner disabled) -->
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text"
                                   id="posSearchInput"
                                   class="form-control border-start-0 bg-light"
                                   placeholder="Search by product name, SKU, or keyword..."
                                   autofocus>
                        </div>
                    </div>
                @endif

                <!-- Category Filter -->
                <div class="col-md-2">
                    <select id="posCategorySelect" class="form-select bg-light" onchange="filterProducts()">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Brand Filter -->
                <div class="col-md-2">
                    <select id="posBrandSelect" class="form-select bg-light" onchange="filterProducts()">
                        <option value="">All Brands</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Product Cards Grid Container -->
        <div class="product-grid-container flex-grow-1 p-3">
            <div class="row g-3" id="productsGrid">
                @forelse ($products as $prod)
                    @php
                        $isOut = $prod->stock_quantity <= 0;
                        $isLow = $prod->stock_quantity > 0 && $prod->stock_quantity <= $prod->alert_quantity;
                    @endphp
                    <div class="col-6 col-sm-4 col-md-4 col-xl-3 product-item"
                         data-id="{{ $prod->id }}"
                         data-name="{{ strtolower($prod->name) }}"
                         data-sku="{{ strtolower($prod->sku) }}"
                         data-barcode="{{ strtolower($prod->barcode ?? '') }}"
                         data-category="{{ $prod->category_id }}"
                         data-brand="{{ $prod->brand_id }}">
                        <div class="card product-card h-100 border-0 rounded-4 shadow-sm p-2.5 d-flex flex-column justify-content-between position-relative {{ $isOut ? 'opacity-50' : '' }}"
                             onclick="{{ $isOut ? 'toastOutOfStock()' : 'addToCart(' . json_encode($prod) . ')' }}">

                            <!-- Product Thumbnail & Badges -->
                            <div class="position-relative mb-2 text-center">
                                <img src="{{ $prod->image_url }}"
                                     alt="{{ $prod->name }}"
                                     class="rounded-3 object-fit-cover w-100"
                                     style="height: 105px;"
                                     loading="lazy">

                                <!-- Stock Badge (Top Right) -->
                                <span class="position-absolute top-0 end-0 m-1 badge {{ $isOut ? 'bg-danger' : ($isLow ? 'bg-warning text-dark' : 'bg-success') }} rounded-pill small">
                                    {{ $isOut ? 'Out of stock' : $prod->stock_quantity . ' ' . $prod->unit }}
                                </span>
                            </div>

                            <!-- Product Info -->
                            <div>
                                <h6 class="fw-bold text-dark mb-0 text-truncate" title="{{ $prod->name }}">{{ $prod->name }}</h6>
                                <div class="d-flex justify-content-between align-items-center small text-muted my-1">
                                    <span class="font-monospace" style="font-size: 0.72rem;">{{ $prod->sku }}</span>
                                    @if($prod->category)
                                        <span class="badge bg-light text-muted border p-1" style="font-size: 0.68rem;">{{ $prod->category->name }}</span>
                                    @endif
                                </div>
                            </div>

                            <!-- Price & Add Button -->
                            <div class="d-flex justify-content-between align-items-center pt-2 mt-auto border-top">
                                <span class="fw-bold text-primary fs-5">${{ number_format($prod->selling_price, 2) }}</span>
                                <button type="button"
                                        class="btn btn-sm btn-primary rounded-circle d-flex align-items-center justify-content-center shadow-xs"
                                        style="width: 32px; height: 32px;"
                                        {{ $isOut ? 'disabled' : '' }}>
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="bi bi-boxes fs-1 d-block mb-2 opacity-50"></i>
                        <h5>No products available in catalog</h5>
                        <p class="small">Add active products with inventory stock to start processing orders.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- RIGHT COLUMN: Customer, Reactive Cart, Totals & Checkout -->
    <!-- ======================================================== -->
    <div class="col-lg-5 col-xl-4 cart-container h-100 d-flex flex-column bg-white shadow">
        @if ($posSettings['enable_customer'])
            <!-- Customer Selection Bar -->
            <div class="p-3 border-bottom bg-white">
                <div class="d-flex align-items-center gap-2">
                    <div class="flex-grow-1">
                        <select id="cartCustomer" class="form-select form-select-sm rounded-pill" onchange="onCustomerSelectChange()">
                            <option value="">-- Walk-in Customer --</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}"
                                        data-points="{{ $c->points }}"
                                        data-balance="{{ $c->balance }}"
                                        data-type="{{ $c->type }}">
                                    {{ $c->name }} ({{ $c->type }} | Points: {{ $c->points }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-xs" data-bs-toggle="modal" data-bs-target="#quickCustomerModal" title="Add Customer">
                        <i class="bi bi-person-plus-fill me-1"></i> New
                    </button>
                </div>
                <!-- Customer Meta Banner (Loyalty points & balance) -->
                <div id="customerMetaBanner" class="d-none mt-2 p-2 bg-light rounded-3 small d-flex justify-content-between">
                    <div>Points: <strong class="text-warning" id="custPointsDisplay">0</strong></div>
                    <div>Balance Due: <strong class="text-danger" id="custBalanceDisplay">{{ $posSettings['currency_symbol'] }}0.00</strong></div>
                </div>
            </div>
        @else
            <!-- Customer Selector Disabled -->
            <input type="hidden" id="cartCustomer" value="">
            <div class="px-3 py-2 border-bottom bg-white d-flex align-items-center justify-content-between">
                <span class="small text-muted"><i class="bi bi-person text-secondary me-1"></i> Customer:</span>
                <span class="badge bg-light text-muted border">Walk-in Customer (Default)</span>
            </div>
        @endif

        <!-- Cart Items List Container -->
        <div class="cart-items flex-grow-1 p-3" id="cartItemsScrollArea">
            <!-- Empty State -->
            <div id="cartEmptyMessage" class="text-center py-5 text-muted my-auto">
                <i class="bi bi-cart3 fs-1 d-block mb-2 text-secondary opacity-50"></i>
                <h6 class="fw-bold">Cart is Empty</h6>
                <p class="small text-muted mb-0">Scan barcode or click products on the left to add.</p>
            </div>

            <!-- Dynamic Cart Rows Container -->
            <div id="cartItemsList" class="d-none"></div>
        </div>

        <!-- Cart Summary, Totals & Checkout -->
        <div class="cart-summary p-3 bg-light border-top">
            <!-- Items Subtotal -->
            <div class="d-flex justify-content-between mb-2 small">
                <span class="text-muted fw-semibold">Subtotal:</span>
                <span id="summarySubtotal" class="fw-bold text-dark">{{ $posSettings['currency_symbol'] }}0.00</span>
            </div>

            <!-- Item Level Discounts Total -->
            <div id="itemDiscountsRow" class="d-flex justify-content-between mb-2 small d-none">
                <span class="text-muted fw-semibold">Item Discounts (-):</span>
                <span id="summaryItemDiscounts" class="fw-bold text-danger">-{{ $posSettings['currency_symbol'] }}0.00</span>
            </div>

            <!-- Order / Cart Discount Input -->
            <div class="d-flex justify-content-between align-items-center mb-2 small">
                <span class="text-muted fw-semibold">Order Discount ({{ $posSettings['currency_symbol'] }}):</span>
                <div style="width: 110px;">
                    <input type="number"
                           id="cartDiscount"
                           class="form-control form-control-sm text-end rounded-pill"
                           value="{{ $posSettings['default_discount'] }}"
                           min="0"
                           step="0.5"
                           oninput="renderCartSummary()">
                </div>
            </div>

            <!-- Tax Rate Input -->
            <div class="d-flex justify-content-between align-items-center mb-2 small">
                <span class="text-muted fw-semibold">Tax Rate (%):</span>
                <div style="width: 110px;">
                    <input type="number"
                           id="cartTaxRate"
                           class="form-control form-control-sm text-end rounded-pill"
                           value="{{ $posSettings['default_tax'] }}"
                           min="0"
                           step="0.5"
                           oninput="renderCartSummary()">
                </div>
            </div>

            <!-- Grand Total -->
            <div class="d-flex justify-content-between align-items-center py-2 my-2 border-top border-bottom border-dark-subtle">
                <h5 class="fw-bold text-dark mb-0">Grand Total:</h5>
                <h3 id="summaryTotal" class="fw-bold text-primary mb-0">{{ $posSettings['currency_symbol'] }}0.00</h3>
            </div>

            <!-- Checkout Action Buttons -->
            <div class="d-grid gap-2 mt-3">
                <button id="checkoutBtn"
                        class="btn btn-primary btn-lg fw-bold py-2.5 rounded-pill shadow-sm"
                        disabled
                        onclick="openCheckoutModal()">
                    <i class="bi bi-credit-card me-2"></i> Pay & Checkout
                </button>
                <button type="button"
                        class="btn btn-outline-danger btn-sm rounded-pill"
                        onclick="confirmClearCart()">
                    <i class="bi bi-trash me-1"></i> Clear Cart
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 1: Checkout & Payment Modal                        -->
<!-- ======================================================== -->
<!-- ======================================================== -->
<!-- MODAL 1: POS Payment & Checkout Modal                     -->
<!-- ======================================================== -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-wallet2 me-2 text-primary"></i> POS Payment & Checkout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Financial Overview Cards: Total, Amount Paid, Change, Remaining Balance -->
                <div class="card bg-light border-0 rounded-4 p-3 mb-3 shadow-xs">
                    <div class="row g-2 text-center align-items-center">
                        <!-- Total -->
                        <div class="col-6 col-sm-3 border-end">
                            <span class="text-muted small text-uppercase d-block fw-semibold" style="font-size: 0.72rem;">Total</span>
                            <h4 class="fw-bold text-dark mb-0" id="modalPayableTotal">$0.00</h4>
                        </div>
                        <!-- Amount Paid -->
                        <div class="col-6 col-sm-3 border-end">
                            <span class="text-muted small text-uppercase d-block fw-semibold" style="font-size: 0.72rem;">Amount Paid</span>
                            <h4 class="fw-bold text-primary mb-0" id="modalAmountPaidDisplay">$0.00</h4>
                        </div>
                        <!-- Change -->
                        <div class="col-6 col-sm-3 border-end">
                            <span class="text-muted small text-uppercase d-block fw-semibold" style="font-size: 0.72rem;">Change</span>
                            <h4 class="fw-bold text-success mb-0" id="changeAmount">$0.00</h4>
                        </div>
                        <!-- Remaining Balance -->
                        <div class="col-6 col-sm-3">
                            <span class="text-muted small text-uppercase d-block fw-semibold" style="font-size: 0.72rem;">Remaining Balance</span>
                            <h4 class="fw-bold text-danger mb-0" id="remainingBalanceDisplay">$0.00</h4>
                        </div>
                    </div>

                    <!-- Payment Status Live Badge -->
                    <div class="d-flex justify-content-center align-items-center gap-2 mt-3 pt-2 border-top">
                        <span class="small text-muted fw-semibold">Payment Status:</span>
                        <span id="paymentStatusBadge" class="badge bg-success fs-6 px-3 py-1 rounded-pill">
                            <i class="bi bi-check-circle-fill me-1"></i> Paid
                        </span>
                    </div>
                </div>

                <!-- Payment Method Selection (7 Methods: Cash, ABA, ACLEDA, Credit Card, Debit Card, Bank Transfer, Other) -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-uppercase text-muted">Select Payment Method <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <!-- Cash -->
                        <div class="col-4 col-md-3">
                            <input type="radio" class="btn-check" name="payment_method" id="payCash" value="cash" checked onchange="calculateChange()">
                            <label class="btn btn-outline-primary w-100 py-2.5 rounded-3 text-center fw-semibold d-flex flex-column align-items-center justify-content-center h-100" for="payCash" style="min-height: 72px;">
                                <i class="bi bi-cash-stack fs-4 mb-1 text-success"></i>
                                <span class="small">Cash</span>
                            </label>
                        </div>
                        <!-- ABA -->
                        <div class="col-4 col-md-3">
                            <input type="radio" class="btn-check" name="payment_method" id="payABA" value="aba" onchange="calculateChange()">
                            <label class="btn btn-outline-primary w-100 py-2.5 rounded-3 text-center fw-semibold d-flex flex-column align-items-center justify-content-center h-100" for="payABA" style="min-height: 72px;">
                                <i class="bi bi-qr-code-scan fs-4 mb-1 text-primary"></i>
                                <span class="small">ABA</span>
                            </label>
                        </div>
                        <!-- ACLEDA -->
                        <div class="col-4 col-md-3">
                            <input type="radio" class="btn-check" name="payment_method" id="payAcleda" value="acleda" onchange="calculateChange()">
                            <label class="btn btn-outline-primary w-100 py-2.5 rounded-3 text-center fw-semibold d-flex flex-column align-items-center justify-content-center h-100" for="payAcleda" style="min-height: 72px;">
                                <i class="bi bi-bank2 fs-4 mb-1 text-warning"></i>
                                <span class="small">ACLEDA</span>
                            </label>
                        </div>
                        <!-- Credit Card -->
                        <div class="col-4 col-md-3">
                            <input type="radio" class="btn-check" name="payment_method" id="payCreditCard" value="credit_card" onchange="calculateChange()">
                            <label class="btn btn-outline-primary w-100 py-2.5 rounded-3 text-center fw-semibold d-flex flex-column align-items-center justify-content-center h-100" for="payCreditCard" style="min-height: 72px;">
                                <i class="bi bi-credit-card-2-front fs-4 mb-1 text-info"></i>
                                <span class="small">Credit Card</span>
                            </label>
                        </div>
                        <!-- Debit Card -->
                        <div class="col-4 col-md-3">
                            <input type="radio" class="btn-check" name="payment_method" id="payDebitCard" value="debit_card" onchange="calculateChange()">
                            <label class="btn btn-outline-primary w-100 py-2.5 rounded-3 text-center fw-semibold d-flex flex-column align-items-center justify-content-center h-100" for="payDebitCard" style="min-height: 72px;">
                                <i class="bi bi-credit-card fs-4 mb-1 text-secondary"></i>
                                <span class="small">Debit Card</span>
                            </label>
                        </div>
                        <!-- Bank Transfer -->
                        <div class="col-4 col-md-3">
                            <input type="radio" class="btn-check" name="payment_method" id="payBankTransfer" value="bank_transfer" onchange="calculateChange()">
                            <label class="btn btn-outline-primary w-100 py-2.5 rounded-3 text-center fw-semibold d-flex flex-column align-items-center justify-content-center h-100" for="payBankTransfer" style="min-height: 72px;">
                                <i class="bi bi-arrow-left-right fs-4 mb-1 text-dark"></i>
                                <span class="small">Bank Transfer</span>
                            </label>
                        </div>
                        <!-- Other -->
                        <div class="col-4 col-md-3">
                            <input type="radio" class="btn-check" name="payment_method" id="payOther" value="other" onchange="calculateChange()">
                            <label class="btn btn-outline-primary w-100 py-2.5 rounded-3 text-center fw-semibold d-flex flex-column align-items-center justify-content-center h-100" for="payOther" style="min-height: 72px;">
                                <i class="bi bi-three-dots fs-4 mb-1 text-muted"></i>
                                <span class="small">Other</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Tendered / Received Amount Input & Quick Buttons -->
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-uppercase text-muted">
                        Amount Paid / Received ({{ $posSettings['currency_symbol'] }}) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-light fw-bold text-muted">{{ $posSettings['currency_symbol'] }}</span>
                        <input type="number"
                               id="tenderedAmount"
                               class="form-control form-control-lg fw-bold text-center fs-2"
                               step="0.01"
                               min="0"
                               placeholder="0.00"
                               oninput="calculateChange()">
                    </div>

                    <!-- Quick Denomination & Shortcut Buttons -->
                    <div class="d-flex flex-wrap gap-1 mt-2">
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-semibold" onclick="setExactTendered()">
                            <i class="bi bi-check-lg me-1"></i> Exact
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5" onclick="setZeroTendered()">
                            {{ $posSettings['currency_symbol'] }}0 (Unpaid)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" onclick="addTendered(5)">+{{ $posSettings['currency_symbol'] }}5</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" onclick="addTendered(10)">+{{ $posSettings['currency_symbol'] }}10</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" onclick="addTendered(20)">+{{ $posSettings['currency_symbol'] }}20</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" onclick="addTendered(50)">+{{ $posSettings['currency_symbol'] }}50</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5" onclick="addTendered(100)">+{{ $posSettings['currency_symbol'] }}100</button>
                    </div>
                </div>

                <!-- Optional Order Memo -->
                <div class="mb-0">
                    <label class="form-label fw-semibold small text-uppercase text-muted">Cashier Memo / Notes (Optional)</label>
                    <input type="text" id="saleNotes" class="form-control" placeholder="Optional notes for this invoice...">
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success rounded-pill px-5 fw-bold shadow" id="confirmPaymentBtn" onclick="submitSale()">
                    <i class="bi bi-check2-circle me-1"></i> Confirm Sale
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 2: Receipt View & Print Modal                      -->
<!-- ======================================================== -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom bg-light">
                <h5 class="modal-title fw-bold text-success">
                    <i class="bi bi-check-circle-fill me-2"></i> Sale Completed!
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="finishSaleFlow()"></button>
            </div>
            <div class="modal-body p-3 font-monospace" style="font-size: 0.85rem; background: #fff;">
                <!-- Thermal style receipt wrapper -->
                <div class="text-center pb-2 border-bottom">
                    <h5 class="fw-bold mb-0 font-monospace">{{ config('app.name', 'POS MANAGEMENT') }}</h5>
                    <small class="text-muted d-block">Official Sales Invoice</small>
                </div>

                <div class="py-2 border-bottom small">
                    <div class="d-flex justify-content-between">
                        <span>Invoice No:</span>
                        <strong class="text-primary" id="receiptInvoiceNo">INV-2026-000001</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Date & Time:</span>
                        <span id="receiptDate">2026-09-04 12:00</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Cashier:</span>
                        <span id="receiptCashier">Staff</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Customer:</span>
                        <span id="receiptCustomer">Walk-in</span>
                    </div>
                </div>

                <!-- Items list -->
                <div class="py-2 border-bottom" style="max-height: 180px; overflow-y: auto;">
                    <table class="table table-sm table-borderless mb-0" style="font-size: 0.8rem;">
                        <thead>
                            <tr class="border-bottom text-muted">
                                <th>Item</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="receiptItemsBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>

                <!-- Financials -->
                <div class="pt-2 small">
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span id="receiptSubtotal">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between text-danger" id="receiptDiscountRow">
                        <span>Discount:</span>
                        <span id="receiptDiscount">-$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between" id="receiptTaxRow">
                        <span>Tax:</span>
                        <span id="receiptTax">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-6 pt-1 border-top mt-1">
                        <span>TOTAL:</span>
                        <span class="text-primary" id="receiptTotal">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between pt-1">
                        <span>Paid (<span id="receiptMethod">Cash</span>):</span>
                        <span class="fw-bold" id="receiptPaid">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Change:</span>
                        <span class="fw-bold text-success" id="receiptChange">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between" id="receiptBalanceRow">
                        <span class="fw-bold text-danger">Remaining Balance:</span>
                        <span class="fw-bold text-danger" id="receiptBalance">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2">
                        <span>Status:</span>
                        <span id="receiptStatusBadge" class="badge bg-success text-uppercase">PAID</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light p-2 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal" onclick="finishSaleFlow()">
                    Next Customer
                </button>
                <div class="d-flex gap-1">
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary fw-bold" id="receiptPrintBtn" onclick="openReceiptPrint('{{ $posSettings['receipt_size'] }}')">
                            <i class="bi bi-printer me-1"></i> Print ({{ strtoupper($posSettings['receipt_size']) }})
                        </button>
                        <button type="button" class="btn btn-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="visually-hidden">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><h6 class="dropdown-header">Thermal / Invoice Print</h6></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="openReceiptPrint('80mm')"><i class="bi bi-printer me-2 text-primary"></i> 80mm Thermal</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="openReceiptPrint('58mm')"><i class="bi bi-printer me-2 text-primary"></i> 58mm Thermal</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="openReceiptPrint('a4')"><i class="bi bi-file-earmark-text me-2 text-info"></i> A4 Invoice</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header">Download PDF (DomPDF)</h6></li>
                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="downloadReceiptPdf('a4')"><i class="bi bi-file-earmark-pdf me-2"></i> A4 PDF</a></li>
                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="downloadReceiptPdf('80mm')"><i class="bi bi-file-earmark-pdf me-2"></i> 80mm PDF</a></li>
                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="downloadReceiptPdf('58mm')"><i class="bi bi-file-earmark-pdf me-2"></i> 58mm PDF</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL 2: Quick Add Customer Modal                        -->
<!-- ======================================================== -->
<div class="modal fade" id="quickCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom">
                <h6 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-1 text-primary"></i> Quick Add Customer</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Customer Name <span class="text-danger">*</span></label>
                    <input type="text" id="quickCustName" class="form-control form-control-sm" placeholder="e.g. John Doe" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Phone Number</label>
                    <input type="text" id="quickCustPhone" class="form-control form-control-sm" placeholder="+1...">
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-semibold">Customer Type</label>
                    <select id="quickCustType" class="form-select form-select-sm">
                        <option value="Regular">Regular</option>
                        <option value="VIP">VIP</option>
                        <option value="Wholesale">Wholesale</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-top p-2 bg-light">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm px-3 fw-bold" onclick="quickSaveCustomer()">Save & Select</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Dynamic POS Configuration from Settings
    const posSettings = @json($posSettings);
    const currency = posSettings.currency_symbol || '$';

    // Web Audio Synthesizer for instant, zero-dependency POS sound effects
    function playPosSound(type = 'beep') {
        if (!posSettings.enable_sound) return;
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);

            if (type === 'beep') {
                osc.frequency.setValueAtTime(820, ctx.currentTime);
                gain.gain.setValueAtTime(0.12, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.08);
                osc.start();
                osc.stop(ctx.currentTime + 0.08);
            } else if (type === 'success') {
                osc.frequency.setValueAtTime(523.25, ctx.currentTime);
                osc.frequency.setValueAtTime(659.25, ctx.currentTime + 0.09);
                osc.frequency.setValueAtTime(783.99, ctx.currentTime + 0.18);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
                osc.start();
                osc.stop(ctx.currentTime + 0.35);
            } else if (type === 'error') {
                osc.frequency.setValueAtTime(240, ctx.currentTime);
                gain.gain.setValueAtTime(0.15, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
                osc.start();
                osc.stop(ctx.currentTime + 0.2);
            }
        } catch (e) {
            // Audio context not allowed without user interaction or not supported
        }
    }

    // In-memory reactive cart state
    let cart = [];

    // -------------------------------------------------------------
    // 1. ADD TO CART & REACTIVE QUANTITY CONTROLS
    // -------------------------------------------------------------
    function addToCart(product) {
        if (!product || product.stock_quantity <= 0) {
            playPosSound('error');
            Toast.fire({ icon: 'error', title: `Product '${product.name}' is out of stock!` });
            return;
        }

        const existing = cart.find(i => i.id === product.id);
        if (existing) {
            if (existing.quantity >= product.stock_quantity) {
                playPosSound('error');
                Toast.fire({
                    icon: 'warning',
                    title: `Cannot exceed available inventory (${product.stock_quantity} in stock)`
                });
                return;
            }
            existing.quantity++;
        } else {
            cart.push({
                id: product.id,
                name: product.name,
                sku: product.sku,
                unit_price: parseFloat(product.selling_price),
                quantity: 1,
                max_stock: parseInt(product.stock_quantity, 10),
                unit: product.unit || 'units',
                discount: 0.00
            });
        }

        playPosSound('beep');
        renderCart();
        Toast.fire({ icon: 'success', title: `Added '${product.name}'` });
    }

    function updateQty(id, delta) {
        const item = cart.find(i => i.id === id);
        if (!item) return;

        const newQty = item.quantity + delta;

        if (newQty > item.max_stock) {
            Toast.fire({
                icon: 'warning',
                title: `Cannot sell more than ${item.max_stock} available units!`
            });
            item.quantity = item.max_stock;
        } else if (newQty <= 0) {
            removeItem(id);
            return;
        } else {
            item.quantity = newQty;
        }

        renderCart();
    }

    function onManualQtyInput(id, inputElement) {
        const item = cart.find(i => i.id === id);
        if (!item) return;

        let val = parseInt(inputElement.value, 10);

        if (isNaN(val) || val <= 0) {
            val = 1;
        }

        if (val > item.max_stock) {
            Toast.fire({
                icon: 'warning',
                title: `Cannot sell more than ${item.max_stock} available units!`
            });
            val = item.max_stock;
        }

        item.quantity = val;
        inputElement.value = val;
        renderCart();
    }

    function onLineDiscountInput(id, inputElement) {
        const item = cart.find(i => i.id === id);
        if (!item) return;

        let val = parseFloat(inputElement.value) || 0.0;
        const maxDiscount = item.unit_price * item.quantity;

        if (val > maxDiscount) {
            val = maxDiscount;
            inputElement.value = val.toFixed(2);
        }

        item.discount = val;
        renderCart();
    }

    function removeItem(id) {
        cart = cart.filter(i => i.id !== id);
        renderCart();
    }

    function confirmClearCart() {
        if (cart.length === 0) return;
        Swal.fire({
            title: 'Clear Cart?',
            text: 'All items will be removed from the current order.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, clear',
            cancelButtonText: 'Keep'
        }).then((res) => {
            if (res.isConfirmed) {
                cart = [];
                renderCart();
            }
        });
    }

    // -------------------------------------------------------------
    // 2. RENDER CART & LIVE TOTALS
    // -------------------------------------------------------------
    function renderCart() {
        const emptyMsg = document.getElementById('cartEmptyMessage');
        const list = document.getElementById('cartItemsList');
        const checkoutBtn = document.getElementById('checkoutBtn');

        if (cart.length === 0) {
            emptyMsg.classList.remove('d-none');
            list.classList.add('d-none');
            checkoutBtn.disabled = true;
            renderCartSummary();
            return;
        }

        emptyMsg.classList.add('d-none');
        list.classList.remove('d-none');
        checkoutBtn.disabled = false;

        list.innerHTML = cart.map(item => {
            const lineSubtotal = (item.unit_price * item.quantity) - (item.discount || 0);
            return `
                <div class="card border-0 bg-light rounded-4 p-2.5 mb-2 shadow-xs">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="pe-2">
                            <h6 class="mb-0 fw-bold text-dark small text-truncate" style="max-width: 200px;" title="${item.name}">${item.name}</h6>
                            <small class="text-muted font-monospace" style="font-size: 0.72rem;">${item.sku} | ${currency}${item.unit_price.toFixed(2)}</small>
                            <small class="text-secondary d-block" style="font-size: 0.7rem;">Max: ${item.max_stock} in stock</small>
                        </div>
                        <button type="button" class="btn btn-sm text-danger p-0 border-0" onclick="removeItem(${item.id})" title="Remove">
                            <i class="bi bi-x-circle-fill fs-5"></i>
                        </button>
                    </div>

                    <!-- Quantity Controls & Line Discount -->
                    <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top border-light-subtle">
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-white border btn-sm px-2" onclick="updateQty(${item.id}, -1)">-</button>
                            <input type="number"
                                   class="form-control form-control-sm text-center fw-bold p-1"
                                   style="width: 52px;"
                                   value="${item.quantity}"
                                   min="1"
                                   max="${item.max_stock}"
                                   onchange="onManualQtyInput(${item.id}, this)">
                            <button type="button" class="btn btn-white border btn-sm px-2" onclick="updateQty(${item.id}, 1)">+</button>
                        </div>

                        <!-- Item Discount Input -->
                        <div class="d-flex align-items-center gap-1">
                            <span class="text-muted" style="font-size: 0.72rem;">Disc:</span>
                            <input type="number"
                                   class="form-control form-control-sm text-end p-1"
                                   style="width: 55px;"
                                   placeholder="0"
                                   min="0"
                                   step="0.5"
                                   value="${item.discount || ''}"
                                   oninput="onLineDiscountInput(${item.id}, this)"
                                   title="Item Discount (${currency})">
                        </div>

                        <!-- Line Subtotal -->
                        <span class="fw-bold text-primary small">${currency}${Math.max(0, lineSubtotal).toFixed(2)}</span>
                    </div>
                </div>
            `;
        }).join('');

        renderCartSummary();
    }

    function getTotals() {
        let rawSubtotal = 0.0;
        let itemDiscounts = 0.0;

        cart.forEach(item => {
            rawSubtotal += item.unit_price * item.quantity;
            itemDiscounts += item.discount || 0.0;
        });

        const subtotalAfterItemDiscounts = Math.max(0, rawSubtotal - itemDiscounts);
        const cartDiscount = parseFloat(document.getElementById('cartDiscount').value) || 0.0;
        const taxableAmount = Math.max(0, subtotalAfterItemDiscounts - cartDiscount);

        const taxRate = parseFloat(document.getElementById('cartTaxRate').value) || 0.0;
        const taxAmount = taxableAmount * (taxRate / 100);

        const grandTotal = taxableAmount + taxAmount;

        return {
            subtotal: rawSubtotal,
            itemDiscounts: itemDiscounts,
            cartDiscount: cartDiscount,
            taxAmount: taxAmount,
            grandTotal: grandTotal
        };
    }

    function renderCartSummary() {
        const { subtotal, itemDiscounts, grandTotal } = getTotals();

        document.getElementById('summarySubtotal').innerText = `${currency}${subtotal.toFixed(2)}`;
        document.getElementById('summaryTotal').innerText = `${currency}${grandTotal.toFixed(2)}`;

        const itemDiscRow = document.getElementById('itemDiscountsRow');
        if (itemDiscounts > 0) {
            itemDiscRow.classList.remove('d-none');
            document.getElementById('summaryItemDiscounts').innerText = `-${currency}${itemDiscounts.toFixed(2)}`;
        } else {
            itemDiscRow.classList.add('d-none');
        }
    }

    // -------------------------------------------------------------
    // 3. BARCODE SCANNER AUTO-ADD HANDLER
    // -------------------------------------------------------------
    const barcodeInput = document.getElementById('barcodeScannerInput');

    if (barcodeInput) {
        barcodeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                triggerBarcodeScan();
            }
        });
    }

    async function triggerBarcodeScan() {
        if (!barcodeInput) return;
        const code = barcodeInput.value.trim();
        if (!code) return;

        try {
            const res = await fetch(`{{ route('pos.barcode-lookup') }}?barcode=${encodeURIComponent(code)}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await res.json();

            if (data.success && data.product) {
                addToCart(data.product);
                barcodeInput.value = '';
                barcodeInput.focus();
            } else {
                playPosSound('error');
                Toast.fire({
                    icon: 'error',
                    title: data.message || 'Product not found!'
                });
            }
        } catch (err) {
            playPosSound('error');
            Toast.fire({ icon: 'error', title: 'Error searching barcode' });
        }
    }

    // -------------------------------------------------------------
    // 4. REAL-TIME PRODUCT CATALOG SEARCH & FILTERS
    // -------------------------------------------------------------
    const searchInput = document.getElementById('posSearchInput');
    const categorySelect = document.getElementById('posCategorySelect');
    const brandSelect = document.getElementById('posBrandSelect');

    function filterProducts() {
        const query = searchInput.value.toLowerCase().trim();
        const catId = categorySelect.value;
        const brandId = brandSelect.value;

        document.querySelectorAll('.product-item').forEach(item => {
            const matchesCat = !catId || item.getAttribute('data-category') === catId;
            const matchesBrand = !brandId || item.getAttribute('data-brand') === brandId;

            const name = item.getAttribute('data-name');
            const sku = item.getAttribute('data-sku');
            const barcode = item.getAttribute('data-barcode');

            const matchesQuery = !query || name.includes(query) || sku.includes(query) || barcode.includes(query);

            if (matchesCat && matchesBrand && matchesQuery) {
                item.classList.remove('d-none');
            } else {
                item.classList.add('d-none');
            }
        });
    }

    searchInput.addEventListener('input', filterProducts);

    function toastOutOfStock() {
        playPosSound('error');
        Toast.fire({ icon: 'error', title: 'This product is currently Out of Stock!' });
    }

    // -------------------------------------------------------------
    // 5. CUSTOMER SELECTION & QUICK ADD
    // -------------------------------------------------------------
    function onCustomerSelectChange() {
        const select = document.getElementById('cartCustomer');
        const banner = document.getElementById('customerMetaBanner');

        if (!select || !banner) return;

        if (!select.value) {
            banner.classList.add('d-none');
            return;
        }

        const opt = select.options[select.selectedIndex];
        const points = opt.dataset.points || '0';
        const balance = parseFloat(opt.dataset.balance) || 0.0;

        const pointsEl = document.getElementById('custPointsDisplay');
        const balanceEl = document.getElementById('custBalanceDisplay');
        if (pointsEl) pointsEl.textContent = points;
        if (balanceEl) balanceEl.textContent = `${currency}${balance.toFixed(2)}`;
        banner.classList.remove('d-none');
    }

    async function quickSaveCustomer() {
        const name = document.getElementById('quickCustName').value.trim();
        if (!name) {
            playPosSound('error');
            Toast.fire({ icon: 'error', title: 'Customer name is required' });
            return;
        }

        try {
            const res = await fetch('{{ route("customers.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    name: name,
                    phone: document.getElementById('quickCustPhone').value.trim(),
                    type: document.getElementById('quickCustType').value
                })
            });

            const data = await res.json();
            if (data.success && data.customer) {
                const select = document.getElementById('cartCustomer');
                if (select) {
                    const opt = new Option(`${data.customer.name} (${data.customer.type})`, data.customer.id, true, true);
                    opt.dataset.points = data.customer.points || '0';
                    opt.dataset.balance = data.customer.balance || '0.00';
                    select.add(opt);
                    onCustomerSelectChange();
                }

                bootstrap.Modal.getInstance(document.getElementById('quickCustomerModal')).hide();
                playPosSound('beep');
                Toast.fire({ icon: 'success', title: 'Customer added and selected' });
            } else {
                playPosSound('error');
                Toast.fire({ icon: 'error', title: data.message || 'Validation error' });
            }
        } catch (e) {
            playPosSound('error');
            Toast.fire({ icon: 'error', title: 'Failed to create customer' });
        }
    }

    // -------------------------------------------------------------
    // 6. CHECKOUT & PAYMENT MODAL LOGIC
    // -------------------------------------------------------------
    let currentCompletedSaleId = null;

    function openCheckoutModal() {
        if (cart.length === 0) {
            playPosSound('error');
            Toast.fire({ icon: 'warning', title: 'Cart is empty!' });
            return;
        }

        const { grandTotal } = getTotals();
        document.getElementById('modalPayableTotal').innerText = `${currency}${grandTotal.toFixed(2)}`;
        document.getElementById('tenderedAmount').value = grandTotal.toFixed(2);
        
        // Reset payment method to Cash
        const defaultMethod = document.getElementById('payCash');
        if (defaultMethod) defaultMethod.checked = true;

        calculateChange();
        new bootstrap.Modal(document.getElementById('checkoutModal')).show();
    }

    function setExactTendered() {
        const { grandTotal } = getTotals();
        document.getElementById('tenderedAmount').value = grandTotal.toFixed(2);
        calculateChange();
    }

    function setZeroTendered() {
        document.getElementById('tenderedAmount').value = '0.00';
        calculateChange();
    }

    function addTendered(amount) {
        const input = document.getElementById('tenderedAmount');
        const curr = parseFloat(input.value) || 0;
        input.value = (curr + amount).toFixed(2);
        calculateChange();
    }

    function calculateChange() {
        const { grandTotal } = getTotals();
        const input = document.getElementById('tenderedAmount');
        let tendered = parseFloat(input.value);
        if (isNaN(tendered) || tendered < 0) {
            tendered = 0.0;
        }

        const change = Math.max(0.0, tendered - grandTotal);
        const remaining = Math.max(0.0, grandTotal - tendered);

        document.getElementById('modalAmountPaidDisplay').innerText = `${currency}${tendered.toFixed(2)}`;
        document.getElementById('changeAmount').innerText = `${currency}${change.toFixed(2)}`;
        document.getElementById('remainingBalanceDisplay').innerText = `${currency}${remaining.toFixed(2)}`;

        // Dynamic Payment Status Badge: Paid, Partial, Unpaid
        const badge = document.getElementById('paymentStatusBadge');
        if (tendered >= grandTotal && grandTotal > 0) {
            badge.className = 'badge bg-success fs-6 px-3 py-1 rounded-pill';
            badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Paid';
        } else if (tendered > 0) {
            badge.className = 'badge bg-warning text-dark fs-6 px-3 py-1 rounded-pill';
            badge.innerHTML = '<i class="bi bi-pie-chart-fill me-1"></i> Partial';
        } else {
            badge.className = 'badge bg-danger fs-6 px-3 py-1 rounded-pill';
            badge.innerHTML = '<i class="bi bi-exclamation-circle-fill me-1"></i> Unpaid';
        }
    }

    async function submitSale() {
        const btn = document.getElementById('confirmPaymentBtn');
        const { subtotal, itemDiscounts, cartDiscount, taxAmount, grandTotal } = getTotals();
        
        let tendered = parseFloat(document.getElementById('tenderedAmount').value);
        if (isNaN(tendered) || tendered < 0) tendered = 0.0;

        const customerSelect = document.getElementById('cartCustomer');
        const customerId = customerSelect ? (customerSelect.value || null) : null;
        const paymentMethod = document.querySelector('input[name="payment_method"]:checked')?.value || 'cash';

        // If partial or unpaid without customer assigned, prompt confirmation
        if (tendered < grandTotal && !customerId) {
            const confirmResult = await Swal.fire({
                title: 'Outstanding Balance Warning',
                text: `Remaining balance of ${currency}${(grandTotal - tendered).toFixed(2)} will not be tracked to a customer account. Proceed as walk-in?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'Go back'
            });

            if (!confirmResult.isConfirmed) {
                return;
            }
        }

        const payload = {
            customer_id: customerId,
            items: cart.map(item => ({
                product_id: item.id,
                quantity: item.quantity,
                unit_price: item.unit_price,
                discount: item.discount || 0.0,
                subtotal: Math.max(0, (item.unit_price * item.quantity) - (item.discount || 0.0))
            })),
            subtotal: subtotal,
            discount_amount: (itemDiscounts + cartDiscount),
            tax_amount: taxAmount,
            total_amount: grandTotal,
            paid_amount: tendered,
            payment_method: paymentMethod,
            notes: document.getElementById('saleNotes').value.trim()
        };

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        try {
            const res = await fetch('{{ route("pos.checkout") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (data.success) {
                playPosSound('success');

                // Close checkout modal
                const checkoutModal = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'));
                if (checkoutModal) checkoutModal.hide();

                // 11. Clear the cart
                cart = [];
                renderCart();

                // 12. Show the receipt
                showReceiptModal(data);

                // 13. Persist transaction into Firebase Firestore in real-time
                try {
                    window.dispatchEvent(new CustomEvent('pos:sale-completed', { detail: data }));
                    if (window.FirebaseSync && typeof window.FirebaseSync.storeSale === 'function') {
                        window.FirebaseSync.storeSale(data);
                    }
                } catch (e) {
                    console.warn('[Firebase] Client-side auto-save notice:', e);
                }
            } else {
                playPosSound('error');
                Swal.fire({
                    icon: 'error',
                    title: 'Transaction Failed',
                    text: data.message || 'Could not complete transaction.'
                });
            }
        } catch (err) {
            playPosSound('error');
            Swal.fire({
                icon: 'error',
                title: 'Server Error',
                text: 'A network error occurred while submitting the order.'
            });
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Confirm Sale';
        }
    }

    // -------------------------------------------------------------
    // 7. RECEIPT MODAL & PRINTING
    // -------------------------------------------------------------
    function showReceiptModal(data) {
        currentCompletedSaleId = data.sale_id;

        document.getElementById('receiptInvoiceNo').innerText = data.invoice_no;
        document.getElementById('receiptDate').innerText = data.sale_date;
        document.getElementById('receiptCashier').innerText = data.cashier_name;
        document.getElementById('receiptCustomer').innerText = data.customer_name;

        // Render items
        const tbody = document.getElementById('receiptItemsBody');
        tbody.innerHTML = (data.items || []).map(item => `
            <tr>
                <td class="text-truncate" style="max-width: 180px;">${item.name}</td>
                <td class="text-center">${item.quantity}</td>
                <td class="text-end">${currency}${item.subtotal}</td>
            </tr>
        `).join('');

        document.getElementById('receiptSubtotal').innerText = `${currency}${data.subtotal}`;
        
        const discRow = document.getElementById('receiptDiscountRow');
        if (parseFloat(data.discount_amount) > 0) {
            discRow.classList.remove('d-none');
            document.getElementById('receiptDiscount').innerText = `-${currency}${data.discount_amount}`;
        } else {
            discRow.classList.add('d-none');
        }

        const taxRow = document.getElementById('receiptTaxRow');
        if (parseFloat(data.tax_amount) > 0) {
            taxRow.classList.remove('d-none');
            document.getElementById('receiptTax').innerText = `${currency}${data.tax_amount}`;
        } else {
            taxRow.classList.add('d-none');
        }

        document.getElementById('receiptTotal').innerText = `${currency}${data.total_amount}`;
        document.getElementById('receiptMethod').innerText = data.payment_method;
        document.getElementById('receiptPaid').innerText = `${currency}${data.paid_amount}`;
        document.getElementById('receiptChange').innerText = `${currency}${data.change_amount}`;

        const balanceRow = document.getElementById('receiptBalanceRow');
        if (parseFloat(data.due_amount) > 0) {
            balanceRow.classList.remove('d-none');
            document.getElementById('receiptBalance').innerText = `${currency}${data.due_amount}`;
        } else {
            balanceRow.classList.add('d-none');
        }

        const badge = document.getElementById('receiptStatusBadge');
        const status = (data.payment_status || 'PAID').toUpperCase();
        badge.innerText = status;
        if (status === 'PAID') {
            badge.className = 'badge bg-success text-uppercase';
        } else if (status === 'PARTIAL') {
            badge.className = 'badge bg-warning text-dark text-uppercase';
        } else {
            badge.className = 'badge bg-danger text-uppercase';
        }

        // Show receipt modal
        new bootstrap.Modal(document.getElementById('receiptModal')).show();
    }

    function openReceiptPrint(format = '80mm') {
        if (!currentCompletedSaleId) return;
        window.open(`/pos/receipt/${currentCompletedSaleId}?format=${format}&autoprint=1`, '_blank');
    }

    function downloadReceiptPdf(format = 'a4') {
        if (!currentCompletedSaleId) return;
        window.location.href = `/pos/receipt/${currentCompletedSaleId}/pdf?format=${format}`;
    }

    function finishSaleFlow() {
        const barcodeInput = document.getElementById('barcodeScannerInput');
        if (barcodeInput) {
            barcodeInput.value = '';
            barcodeInput.focus();
        }
    }
</script>
@endpush
