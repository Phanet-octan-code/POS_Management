@extends('layouts.app')

@section('title', 'Expense Report')

@section('content')
<div class="container-fluid p-0">
    @include('reports.partials.header', [
        'title' => 'Expense Report',
        'subtitle' => 'Audit of operating expenditures, utility overheads, category breakdowns, and staff assignments.',
        'icon' => 'bi-wallet2',
        'dateRange' => $dateRange
    ])

    <!-- Summary KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <x-stat-box
                title="Total Operating Expenses"
                value="${{ number_format($summary['total_expenses'], 2) }}"
                icon="bi-wallet2"
                color="danger"
                subtext="Cumulative overhead costs"
            />
        </div>
        <div class="col-xl-4 col-md-6">
            <x-stat-box
                title="Expense Vouchers"
                value="{{ number_format($summary['total_count']) }}"
                icon="bi-receipt"
                color="secondary"
                subtext="Logged expense entries"
            />
        </div>
        <div class="col-xl-4 col-md-12">
            <x-stat-box
                title="Average Expense"
                value="${{ number_format($summary['avg_expense'], 2) }}"
                icon="bi-calculator"
                color="info"
                subtext="Mean cost per entry"
            />
        </div>
    </div>

    <!-- Category Cost Breakdown Badges -->
    @if(!empty($summary['category_breakdown']) && count($summary['category_breakdown']) > 0)
        <div class="card shadow-sm border-0 mb-4 rounded-4">
            <div class="card-body p-3">
                <small class="text-muted text-uppercase fw-bold d-block mb-2">Expenses by Category</small>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($summary['category_breakdown'] as $cat)
                        <span class="badge bg-{{ $cat['category_color'] }}-subtle text-dark border px-3 py-2 fs-7 d-flex align-items-center gap-2">
                            <span>{{ $cat['category_name'] }}</span>
                            <span class="fw-bold text-danger">${{ number_format($cat['total'], 2) }}</span>
                            <span class="badge bg-white text-muted border rounded-pill small">{{ $cat['count'] }} entries</span>
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Data Table -->
    <x-card>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small fw-bold">
                    <tr>
                        <th>Ref #</th>
                        <th>
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'expense_date', 'sort_dir' => request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center gap-1">
                                Date
                                @if(request('sort_by', 'expense_date') === 'expense_date')
                                    <i class="bi bi-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </a>
                        </th>
                        <th>Expense Name</th>
                        <th>Category</th>
                        <th>Recorded By</th>
                        <th class="text-end">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'amount', 'sort_dir' => request('sort_dir') === 'asc' ? 'desc' : 'asc']) }}" class="text-decoration-none text-dark d-flex align-items-center justify-content-end gap-1">
                                Amount
                                @if(request('sort_by') === 'amount')
                                    <i class="bi bi-arrow-{{ request('sort_dir') === 'asc' ? 'up' : 'down' }} text-primary"></i>
                                @endif
                            </a>
                        </th>
                        <th>Notes / Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $exp)
                        <tr>
                            <td><code>{{ $exp->reference_no }}</code></td>
                            <td>{{ $exp->expense_date->format('M d, Y') }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $exp->title }}</div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $exp->category_color }} px-2 py-1">
                                    {{ $exp->category?->name ?? 'Uncategorized' }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted small">
                                    <i class="bi bi-person me-1"></i> {{ $exp->user?->name ?? 'Staff' }}
                                </span>
                            </td>
                            <td class="text-end fw-bold text-danger fs-6">
                                ${{ number_format($exp->amount, 2) }}
                            </td>
                            <td>
                                <small class="text-muted">{{ $exp->notes ?? 'None' }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-wallet2 fs-1 d-block mb-2 text-muted"></i>
                                <div class="fw-semibold">No expense entries found for this period.</div>
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
