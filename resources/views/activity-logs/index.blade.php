@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
<div class="container-fluid p-0">
    <!-- Page Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-1">Audit Activity Logs</h3>
            <p class="text-muted mb-0">Comprehensive audit trail recording all user actions, operational modules, timestamps, and network IPs.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill font-monospace">
                <i class="bi bi-clock-history me-1 text-primary"></i> Total: {{ number_format($totalLogsCount) }} logs
            </span>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-semibold">
                <i class="bi bi-lightning-fill me-1"></i> Today: {{ number_format($todayLogsCount) }}
            </span>
        </div>
    </div>

    <!-- Search and Multi-Filter Toolbar Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
        <form action="{{ route('activity-logs.index') }}" method="GET" id="activityFilterForm">
            <div class="row g-2 align-items-center">
                <!-- Keyword Search -->
                <div class="col-12 col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted ps-2.5">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text"
                               name="q"
                               class="form-control bg-light border-start-0 py-2"
                               placeholder="Search user, action, description, IP..."
                               value="{{ $search ?? '' }}">
                    </div>
                </div>

                <!-- Module Filter -->
                <div class="col-6 col-md-2">
                    <select name="module" class="form-select form-select-sm py-2 bg-light">
                        <option value="all">All Modules</option>
                        @foreach ($modules as $slug => $label)
                            <option value="{{ $slug }}" {{ ($module ?? '') === $slug ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Filter -->
                <div class="col-6 col-md-2">
                    <select name="action" class="form-select form-select-sm py-2 bg-light">
                        <option value="all">All Actions</option>
                        @foreach ($actions as $slug => $label)
                            <option value="{{ $slug }}" {{ ($action ?? '') === $slug ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- User Filter -->
                <div class="col-6 col-md-2">
                    <select name="user_id" class="form-select form-select-sm py-2 bg-light">
                        <option value="all">All Users</option>
                        @foreach ($users as $u)
                            <option value="{{ $u->id }}" {{ ($userId ?? '') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->primaryRoleName() }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Preset Filter -->
                <div class="col-6 col-md-2">
                    <select name="date_preset" class="form-select form-select-sm py-2 bg-light" id="datePresetSelect" onchange="toggleCustomDates(this.value)">
                        <option value="">All Time</option>
                        <option value="today" {{ ($datePreset ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ ($datePreset ?? '') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ ($datePreset ?? '') === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ ($datePreset ?? '') === 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_30_days" {{ ($datePreset ?? '') === 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="custom" {{ ($datePreset ?? '') === 'custom' || (!empty($startDate) || !empty($endDate)) ? 'selected' : '' }}>Custom Date Range</option>
                    </select>
                </div>

                <!-- Submit & Reset Buttons -->
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm py-2 w-100 fw-bold" title="Apply Filter">
                        <i class="bi bi-funnel-fill"></i>
                    </button>
                    <a href="{{ route('activity-logs.index') }}" class="btn btn-outline-secondary btn-sm py-2 px-2.5" title="Reset Filters">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                </div>
            </div>

            <!-- Optional Custom Date Inputs -->
            <div class="row g-2 mt-1 {{ ($datePreset ?? '') === 'custom' || (!empty($startDate) || !empty($endDate)) ? '' : 'd-none' }}" id="customDateRow">
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-0">From Date</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate ?? '' }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small text-muted mb-0">To Date</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate ?? '' }}">
                </div>
            </div>
        </form>
    </div>

    <!-- Activity Log Results Table Card -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 14%;">Date & Time</th>
                        <th style="width: 18%;">User</th>
                        <th style="width: 12%;">Action</th>
                        <th style="width: 12%;">Module</th>
                        <th style="width: 32%;">Description</th>
                        <th style="width: 12%;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $actionLower = strtolower($log->action);
                            $badgeColor = 'secondary';
                            if (str_contains($actionLower, 'create') || str_contains($actionLower, 'add')) {
                                $badgeColor = 'success';
                            } elseif (str_contains($actionLower, 'update') || str_contains($actionLower, 'edit') || str_contains($actionLower, 'adjust')) {
                                $badgeColor = 'primary';
                            } elseif (str_contains($actionLower, 'delete') || str_contains($actionLower, 'remove')) {
                                $badgeColor = 'danger';
                            } elseif (str_contains($actionLower, 'complete') || str_contains($actionLower, 'sale')) {
                                $badgeColor = 'purple';
                            }
                        @endphp
                        <tr>
                            <!-- 1. Date & Time -->
                            <td>
                                <div class="fw-bold text-dark small">{{ $log->formatted_date }}</div>
                                <small class="text-muted font-monospace" style="font-size: 0.72rem;">
                                    <i class="bi bi-clock me-1 text-secondary"></i>{{ $log->formatted_time }}
                                </small>
                            </td>

                            <!-- 2. User & Role -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.8rem;">
                                        {{ strtoupper(substr($log->user?->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small text-truncate" style="max-width: 130px;">
                                            {{ $log->user?->name ?? 'System' }}
                                        </div>
                                        <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.65rem;">
                                            {{ $log->user?->primaryRoleName() ?? 'System' }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- 3. Action -->
                            <td>
                                <span class="badge bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} border border-{{ $badgeColor }}-subtle font-monospace px-2 py-1">
                                    {{ $log->action }}
                                </span>
                            </td>

                            <!-- 4. Module -->
                            <td>
                                <span class="badge bg-light text-dark border px-2 py-1 text-capitalize">
                                    <i class="bi bi-folder2-open me-1 text-primary"></i>{{ $log->module ?: 'General' }}
                                </span>
                            </td>

                            <!-- 5. Description -->
                            <td>
                                <span class="text-dark small fw-medium">
                                    {{ $log->description ?? '—' }}
                                </span>
                            </td>

                            <!-- 6. IP Address -->
                            <td>
                                <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.75rem;">
                                    <i class="bi bi-hdd-network me-1"></i>{{ $log->ip_address ?: '127.0.0.1' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-1 text-secondary d-block mb-2 opacity-50"></i>
                                <h6 class="fw-bold text-dark mb-1">No activity logs found</h6>
                                <p class="small text-muted mb-0">Try changing or clearing your search and filter criteria.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="card-footer bg-light border-top p-3 d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} activity logs
                </small>
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

<script>
function toggleCustomDates(val) {
    const customRow = document.getElementById('customDateRow');
    if (val === 'custom') {
        customRow.classList.remove('d-none');
    } else {
        customRow.classList.add('d-none');
    }
}
</script>
@endsection
