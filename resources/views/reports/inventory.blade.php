@extends('layouts.app')

@section('title', 'Inventory Report')

@section('content')
<div class="container-fluid p-0">
    @include('reports.partials.header', [
        'title' => 'Inventory Report',
        'subtitle' => 'Live inventory valuation, cost holdings, potential retail value, and alert stock positions.',
        'icon' => 'bi-box-seam',
        'dateRange' => $dateRange
    ])

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Catalog Products"
                value="{{ number_format($summary['total_items']) }}"
                icon="bi-box"
                color="dark"
                subtext="Registered product items"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Total Stock Units"
                value="{{ number_format($summary['total_units']) }}"
                icon="bi-layers"
                color="info"
                subtext="Units on shelves & warehouse"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Inventory Cost Value"
                value="${{ number_format($summary['total_cost_value'], 2) }}"
                icon="bi-cash-coin"
                color="primary"
                subtext="Total capital tied in stock"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Potential Gross Profit"
                value="${{ number_format($summary['potential_profit'], 2) }}"
                icon="bi-graph-up-arrow"
                color="success"
                subtext="Retail: ${{ number_format($summary['total_retail_value'], 2) }}"
            />
        </div>
    </div>

    <!-- Data Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_dir' => request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                Product Name
                                @if(request('sort_by') === 'name')
                                    <i class="bi bi-arrow-{{ request('sort_dir') === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </a>
                        </th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th class="text-end">Cost</th>
                        <th class="text-end">Price</th>
                        <th class="text-center">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'stock_quantity', 'sort_dir' => request('sort_dir', 'asc') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center justify-content-center gap-1">
                                Current Stock
                                @if(request('sort_by', 'stock_quantity') === 'stock_quantity')
                                    <i class="bi bi-arrow-{{ request('sort_dir', 'asc') === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </a>
                        </th>
                        <th class="text-center">Alert</th>
                        <th class="text-end">Stock Cost Value</th>
                        <th class="text-end">Retail Value</th>
                        <th class="text-end fw-bold">Potential Profit</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $prod)
                        @php
                            $qty = max(0, $prod->stock_quantity);
                            $costVal = $qty * (float) $prod->cost_price;
                            $retailVal = $qty * (float) $prod->selling_price;
                            $profitVal = $retailVal - $costVal;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $prod->name }}</div>
                            </td>
                            <td><code>{{ $prod->sku }}</code></td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $prod->category?->name ?? 'None' }}</span>
                            </td>
                            <td class="text-end">${{ number_format($prod->cost_price, 2) }}</td>
                            <td class="text-end">${{ number_format($prod->selling_price, 2) }}</td>
                            <td class="text-center fw-bold fs-6 {{ $prod->stock_quantity <= 0 ? 'text-danger' : ($prod->stock_quantity <= $prod->alert_quantity ? 'text-warning' : 'text-dark') }}">
                                {{ $prod->stock_quantity }} {{ $prod->unit }}
                            </td>
                            <td class="text-center text-muted">{{ $prod->alert_quantity }} {{ $prod->unit }}</td>
                            <td class="text-end">${{ number_format($costVal, 2) }}</td>
                            <td class="text-end">${{ number_format($retailVal, 2) }}</td>
                            <td class="text-end fw-bold text-success">
                                ${{ number_format($profitVal, 2) }}
                            </td>
                            <td class="text-center">
                                @if($prod->stock_quantity <= 0)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Out of Stock</span>
                                @elseif($prod->stock_quantity <= $prod->alert_quantity)
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Low Stock</span>
                                @else
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">In Stock</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-2 text-muted"></i>
                                <div class="fw-semibold">No inventory products found matching filters.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-3 border-top">
                {{ $records->links() }}
            </div>
        @endif
    </x-card>
</div>
@endsection
