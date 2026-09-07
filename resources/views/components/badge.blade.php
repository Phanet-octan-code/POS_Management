@props([
    'type' => 'primary',
])

<span {{ $attributes->merge(['class' => "badge bg-{$type}-subtle text-{$type} border border-{$type}-subtle px-2.5 py-1 rounded-pill fw-semibold"]) }}>
    {{ $slot }}
</span>
