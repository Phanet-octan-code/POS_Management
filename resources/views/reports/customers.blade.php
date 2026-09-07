@extends('layouts.app')

@section('title', 'Customer Report')

@section('content')
<div class="container-fluid p-0">
    @include('reports.partials.header', [
        'title' => 'Customer Report',
        'subtitle' => 'Customer order frequencies, lifetime sales contribution, and outstanding balances.',
        'icon' => 'bi-people',
        'dateRange' => $dateRange
    ])

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Registered Customers"
                value="{{ number_format($summary['total_customers']) }}"
                icon="bi-people"
                color="info"
                subtext="Total customer base"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Orders in Period"
                value="{{ number_format($summary['total_orders']) }}"
                icon="bi-cart-check"
                color="secondary"
                subtext="Completed by customers"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Customer Revenue"
                value="${{ number_format($summary['total_revenue'], 2) }}"
                icon="bi-currency-dollar"
                color="primary"
                subtext="Sales revenue generated"
            />
        </div>
        <div class="col-xl-3 col-md-6">
            <x-stat-box
                title="Total Receivables Due"
                value="${{ number_format($summary['total_receivables'], 2) }}"
                icon="bi-cash"
                color="danger"
                subtext="Outstanding credit balance"
            />
        </div>
    </div>

    <!-- Data Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th class="text-center">Type</th>
                        <th class="text-center">Orders (Period)</th>
                        <th class="text-end">Total Spent (Period)</th>
                        <th class="text-end">Balance Due</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $cust)
                        <tr>
                            <td>
                                <a href="{{ route('customers.show', $cust) }}" class="fw-bold text-primary text-decoration-none">
                                    {{ $cust->name }}
                                </a>
                            </td>
                            <td>{{ $cust->phone ?? 'N/A' }}</td>
                            <td>{{ $cust->email ?? 'N/A' }}</td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border text-uppercase" style="font-size: 0.72rem;">
                                    {{ $cust->customer_type }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fw-bold">
                                    {{ $cust->sales_count }} orders
                                </span>
                            </td>
                            <td class="text-end fw-bold text-dark fs-6">
                                ${{ number_format($cust->sales_sum_total_amount ?? 0, 2) }}
                            </td>
                            <td class="text-end fw-bold {{ $cust->balance > 0 ? 'text-danger' : 'text-success' }}">
                                ${{ number_format($cust->balance, 2) }}
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $cust->status === 'active' ? 'success' : 'secondary' }}-subtle text-dark border px-2 py-1">
                                    {{ ucfirst($cust->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 text-muted"></i>
                                <div class="fw-semibold">No customer records found.</div>
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
