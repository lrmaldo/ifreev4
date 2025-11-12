<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="{{ asset('img/Logo i-Free.png') }}" sizes="any">
<link rel="icon" href="{{ asset('img/Logo i-Free.png') }}" type="image/svg+xml">
<link rel="apple-touch-icon" href="{{ asset('img/Logo i-Free.png') }}">

{{-- autor --}}
<meta name="author" content="i-Free - Portal Cautivo WiFi" />
{{-- descripción --}}
<meta name="description" content="{{ $description ?? 'i-Free - Portal Cautivo WiFi' }}" />
{{-- palabras clave --}}
<meta name="keywords" content="{{ $keywords ?? 'i-Free, Portal Cautivo, WiFi, Hotspot, Acceso a Internet' }}" />
{{-- robots --}}
<meta name="robots" content="index, follow" />
{{-- tema color --}}
<meta name="theme-color" content="#2563EB" />

{{-- SEO Meta Tags --}}
<meta property="og:title" content="{{ $title ?? config('app.name') }}" />
<meta property="og:description" content="{{ $description ?? 'i-Free ' }}" />
<meta property="og:type" content="website" />
<meta property="og:url" content="{{ url()->current() }}" />
<meta property="og:image" content="{{ asset('img/Logo i-Free.png') }}" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ $title ?? config('app.name') }}" />
<meta name="twitter:description" content="{{ $description ?? 'i-Free - Portal Cautivo WiFi' }}" />
<meta name="twitter:image" content="{{ asset('img/Logo i-Free.png') }}" />

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance

<!-- Select2 CSS y JS -->
@stack('styles')
@stack('scripts')
