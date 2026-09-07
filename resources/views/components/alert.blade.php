@props([
    'type' => 'info',
    'dismissible' => true,
])

<div {{ $attributes->merge(['class' => "alert alert-{$type} " . ($dismissible ? 'alert-dismissible fade show' : '') . " border-0 shadow-sm rounded-3 py-3 px-4 mb-4"]) }} role="alert">
    {{ $slot }}
    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
