@props([
    'title',
    'value',
    'icon' => 'bi-graph-up',
    'color' => 'primary',
    'subtext' => null,
    'badge' => null,
])

<div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden" style="border: 1px solid #e2e8f0; transition: transform 0.2s, box-shadow 0.2s;">
    <div class="card-body p-4 position-relative">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <span class="text-muted fw-semibold text-uppercase small" style="letter-spacing: 0.05em; font-size: 0.76rem;">{{ $title }}</span>
            <div class="rounded-3 p-2 bg-{{ $color }}-subtle text-{{ $color }} d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                <i class="bi {{ $icon }} fs-4"></i>
            </div>
        </div>
        <div class="d-flex align-items-baseline gap-2">
            <h3 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.5px;">{{ $value }}</h3>
            @if ($badge)
                <span class="badge bg-{{ $color }}-subtle text-{{ $color }} rounded-pill small px-2 py-0.5">{{ $badge }}</span>
            @endif
        </div>
        @if ($subtext)
            <div class="text-muted small mt-2 d-flex align-items-center gap-1" style="font-size: 0.82rem;">
                {{ $subtext }}
            </div>
        @endif
    </div>
</div>
