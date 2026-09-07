<div class="mb-4">
    <!-- Top Row: Title and Multi-format Export Actions -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
        <div>
            <h3 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <i class="bi {{ $icon ?? 'bi-bar-chart-line' }} text-primary"></i>
                <span>{{ $title }}</span>
            </h3>
            <p class="text-muted small mb-0">{{ $subtitle ?? 'Financial statements and business analytics.' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="text-muted small fw-semibold me-1"><i class="bi bi-download me-1"></i> Export:</span>
            <!-- PDF Export -->
            <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="btn btn-sm btn-outline-danger shadow-sm rounded-pill px-3" title="Export as PDF Document">
                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
            </a>
            <!-- Excel Export -->
            <a href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}" class="btn btn-sm btn-outline-success shadow-sm rounded-pill px-3" title="Export as Microsoft Excel (.xls)">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
            <!-- CSV Export -->
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-sm btn-outline-secondary shadow-sm rounded-pill px-3" title="Export as Raw CSV">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> CSV
            </a>
        </div>
    </div>

    <!-- 7 Reports Sub-Navigation Tabs -->
    <div class="card shadow-sm border-0 mb-4 rounded-4">
        <div class="card-body p-2">
            <ul class="nav nav-pills nav-fill flex-column flex-sm-row gap-1">
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold {{ request()->routeIs('reports.sales') || request()->routeIs('reports.index') ? 'active bg-primary shadow-sm text-white' : 'text-dark' }}" href="{{ route('reports.sales', request()->only('date_filter', 'start_date', 'end_date')) }}">
                        <i class="bi bi-receipt me-1"></i> 1. Sales Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold {{ request()->routeIs('reports.purchases') ? 'active bg-primary shadow-sm text-white' : 'text-dark' }}" href="{{ route('reports.purchases', request()->only('date_filter', 'start_date', 'end_date')) }}">
                        <i class="bi bi-bag-check me-1"></i> 2. Purchase Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold {{ request()->routeIs('reports.profit') ? 'active bg-primary shadow-sm text-white' : 'text-dark' }}" href="{{ route('reports.profit', request()->only('date_filter', 'start_date', 'end_date')) }}">
                        <i class="bi bi-graph-up-arrow me-1"></i> 3. Profit Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold {{ request()->routeIs('reports.inventory') ? 'active bg-primary shadow-sm text-white' : 'text-dark' }}" href="{{ route('reports.inventory', request()->only('date_filter', 'start_date', 'end_date')) }}">
                        <i class="bi bi-box-seam me-1"></i> 4. Inventory Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold {{ request()->routeIs('reports.customers') ? 'active bg-primary shadow-sm text-white' : 'text-dark' }}" href="{{ route('reports.customers', request()->only('date_filter', 'start_date', 'end_date')) }}">
                        <i class="bi bi-people me-1"></i> 5. Customer Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold {{ request()->routeIs('reports.suppliers') ? 'active bg-primary shadow-sm text-white' : 'text-dark' }}" href="{{ route('reports.suppliers', request()->only('date_filter', 'start_date', 'end_date')) }}">
                        <i class="bi bi-truck me-1"></i> 6. Supplier Report
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-2 px-3 fw-semibold {{ request()->routeIs('reports.expenses') ? 'active bg-primary shadow-sm text-white' : 'text-dark' }}" href="{{ route('reports.expenses', request()->only('date_filter', 'start_date', 'end_date')) }}">
                        <i class="bi bi-wallet2 me-1"></i> 7. Expense Report
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <x-card class="mb-4">
        <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end">
            @php
                $curFilter = request('date_filter', 'this_month');
            @endphp

            <!-- Quick Date Filter Presets -->
            <div class="col-12 col-xl-5">
                <label class="form-label small fw-semibold text-muted mb-1 d-block">Period Quick Presets</label>
                <div class="btn-group w-100 p-1 bg-light rounded-pill border" role="group">
                    <a href="{{ request()->fullUrlWithQuery(['date_filter' => 'today', 'start_date' => null, 'end_date' => null]) }}"
                       class="btn btn-sm rounded-pill px-2.5 {{ $curFilter === 'today' ? 'btn-primary active text-white fw-bold shadow-sm' : 'btn-light text-dark' }}">
                        Today
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['date_filter' => 'yesterday', 'start_date' => null, 'end_date' => null]) }}"
                       class="btn btn-sm rounded-pill px-2.5 {{ $curFilter === 'yesterday' ? 'btn-primary active text-white fw-bold shadow-sm' : 'btn-light text-dark' }}">
                        Yesterday
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['date_filter' => 'this_week', 'start_date' => null, 'end_date' => null]) }}"
                       class="btn btn-sm rounded-pill px-2.5 {{ $curFilter === 'this_week' ? 'btn-primary active text-white fw-bold shadow-sm' : 'btn-light text-dark' }}">
                        This Week
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['date_filter' => 'this_month', 'start_date' => null, 'end_date' => null]) }}"
                       class="btn btn-sm rounded-pill px-2.5 {{ $curFilter === 'this_month' ? 'btn-primary active text-white fw-bold shadow-sm' : 'btn-light text-dark' }}">
                        This Month
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['date_filter' => 'this_year', 'start_date' => null, 'end_date' => null]) }}"
                       class="btn btn-sm rounded-pill px-2.5 {{ $curFilter === 'this_year' ? 'btn-primary active text-white fw-bold shadow-sm' : 'btn-light text-dark' }}">
                        This Year
                    </a>
                </div>
            </div>

            <!-- Custom Date Range -->
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small fw-semibold text-muted mb-1">From Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $dateRange['start_date'] ?? request('start_date') }}">
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <label class="form-label small fw-semibold text-muted mb-1">To Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $dateRange['end_date'] ?? request('end_date') }}">
                <input type="hidden" name="date_filter" value="custom">
            </div>

            <!-- Search Field -->
            <div class="col-12 col-md-4 col-xl-2">
                <label class="form-label small fw-semibold text-muted mb-1">Search Records</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Keywords..." value="{{ request('search') }}">
                </div>
            </div>

            <!-- Actions -->
            <div class="col-12 col-md-2 col-xl-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100" title="Apply Filter">
                    <i class="bi bi-filter"></i> Apply
                </button>
                @if(request()->hasAny(['search', 'date_filter', 'start_date', 'end_date', 'sort_by', 'sort_dir']))
                    <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary" title="Reset All Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                @endif
            </div>
        </form>
    </x-card>
</div>
