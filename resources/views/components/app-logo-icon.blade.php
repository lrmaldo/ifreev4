<?php
<div {{ $attributes->merge(['class' => 'app-logo-container']) }}>
    <img
        src="{{ asset('img/Logo i-Free.png') }}"
        alt="i-Free Logo"
        class="app-logo-image"
        style="max-width: 100%; height: auto; object-fit: contain;"
    />
</div>

<style>
.app-logo-container {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.app-logo-image {
    border-radius: 8px;
    transition: all 0.3s ease;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.app-logo-image:hover {
    transform: scale(1.05);
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.15));
}

/* Variantes de tamaño */
.app-logo-container.size-sm .app-logo-image {
    max-height: 32px;
}

.app-logo-container.size-md .app-logo-image {
    max-height: 48px;
}

.app-logo-container.size-lg .app-logo-image {
    max-height: 64px;
}

.app-logo-container.size-xl .app-logo-image {
    max-height: 80px;
}

/* Para fondos oscuros */
.app-logo-container.dark .app-logo-image {
    filter: brightness(1.1) drop-shadow(0 2px 4px rgba(255, 255, 255, 0.1));
}

/* Responsive */
@media (max-width: 768px) {
    .app-logo-image {
        max-height: 40px;
    }
}
</style>
