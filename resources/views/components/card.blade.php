@props([
    'title' => null,
    'subtitle' => null,
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm rounded-4 ' . $class]) }}>
    @if ($title || isset($headerActions))
        <div class="card-header bg-white border-bottom border-light-subtle py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                @if ($title)
                    <h5 class="card-title fw-bold text-dark mb-0">{{ $title }}</h5>
                @endif
                @if ($subtitle)
                    <small class="text-muted">{{ $subtitle }}</small>
                @endif
            </div>
            @if (isset($headerActions))
                <div>
                    {{ $headerActions }}
                </div>
            @endif
        </div>
    @endif
    <div class="card-body p-4">
        {{ $slot }}
    </div>
    @if (isset($footer))
        <div class="card-footer bg-white border-top border-light-subtle px-4 py-3">
            {{ $footer }}
        </div>
    @endif
</div>
