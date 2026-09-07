@extends('layouts.app')

@section('title', 'Supplier Report')

@section('content')
<div class="container-fluid p-0">
    @include('reports.partials.header', [
        'title' => 'Supplier Report',
        'subtitle' => 'Vendor sourcing metrics, purchase order volumes, and outstanding accounts payable.',
        'icon' => 'bi-truck',
        'dateRange' => $dateRange
    ])

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Active Suppliers"
                value="{{ number_format($summary['total_suppliers']) }}"
                icon="bi-truck"
                color="info"
                subtext="Registered vendor partners"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Purchase Orders"
                value="{{ number_format($summary['total_orders']) }}"
                icon="bi-bag-check"
                color="secondary"
                subtext="Orders in selected period"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Total Sourced Amount"
                value="${{ number_format($summary['total_purchased'], 2) }}"
                icon="bi-currency-dollar"
                color="primary"
                subtext="Procurement expenditure"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Total Payables Due"
                value="${{ number_format($summary['total_payables'], 2) }}"
                icon="bi-hourglass-split"
                color="danger"
                subtext="Outstanding vendor debt"
            />
        </div>
    </div>

    <!-- Data Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th>Supplier Name</th>
                        <th>Company</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th class="text-center">Purchases (Period)</th>
                        <th class="text-end">Total Sourced (Period)</th>
                        <th class="text-end">Balance Payable</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $sup)
                        <tr>
                            <td>
                                <a href="{{ route('suppliers.show', $sup) }}" class="fw-bold text-primary text-decoration-none">
                                    {{ $sup->name }}
                                </a>
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">{{ $sup->company ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $sup->phone ?? 'N/A' }}</td>
                            <td>{{ $sup->email ?? 'N/A' }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fw-bold">
                                    {{ $sup->purchases_count }} orders
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark fs-6">
                                ${{ number_format($sup->purchases_sum_total_amount ?? 0, 2) }}
                            </td>
                            <td class="text-end fw-bold {{ $sup->balance > 0 ? 'text-danger' : 'text-success' }}">
                                ${{ number_format($sup->balance, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $sup->status === 'active' ? 'success' : 'secondary' }}-subtle text-dark border px-2 py-1">
                                    {{ ucfirst($sup->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-truck fs-1 d-block mb-2 text-muted"></i>
                                <div class="fw-semibold">No supplier records found.</div>
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
