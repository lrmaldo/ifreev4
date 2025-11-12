<?php
@props([
    'size' => 'md', // sm, md, lg, xl
    'variant' => 'default', // default, dark, light
    'showText' => false,
    'href' => null
])

@php
$sizeClasses = [
    'sm' => 'h-8 w-8',
    'md' => 'h-12 w-12',
    'lg' => 'h-16 w-16',
    'xl' => 'h-20 w-20'
];

$containerClass = 'inline-flex items-center justify-center app-logo-container size-' . $size;
if ($variant === 'dark') $containerClass .= ' dark';

$imageClasses = $sizeClasses[$size] . ' object-contain transition-all duration-300 ease-in-out hover:scale-105';
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $containerClass]) }}>
        <img
            src="{{ asset('img/Logo i-Free.png') }}"
            alt="i-Free - Portal Cautivo WiFi"
            class="{{ $imageClasses }}"
        />
        @if($showText)
            <span class="ml-3 text-xl font-bold text-gray-900 dark:text-white">
                i-Free
            </span>
        @endif
    </a>
@else
    <div {{ $attributes->merge(['class' => $containerClass]) }}>
        <img
            src="{{ asset('img/Logo i-Free.png') }}"
            alt="i-Free - Portal Cautivo WiFi"
            class="{{ $imageClasses }}"
        />
        @if($showText)
            <span class="ml-3 text-xl font-bold text-gray-900 dark:text-white">
                i-Free
            </span>
        @endif
    </div>
@endif

<style>
.app-logo-container img {
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.app-logo-container.dark img {
    filter: brightness(1.1) drop-shadow(0 2px 4px rgba(255, 255, 255, 0.1));
}

.app-logo-container img:hover {
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
}

.app-logo-container.dark img:hover {
    filter: brightness(1.1) drop-shadow(0 4px 8px rgba(255, 255, 255, 0.15));
}
</style>
