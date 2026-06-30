<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('messages.install.title'))</title>

    @php
        $assetVersion = rawurlencode(\App\Support\PanelVersion::current());
    @endphp

    <script>
        (function() {
            document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'dark');
        })();
    </script>

    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}?v={{ $assetVersion }}">
</head>
<body class="install-shell d-flex align-items-center justify-content-center py-4">
    <main class="container">
        <div class="install-card install-card-wide mx-auto">
            <div class="text-center mb-4">
                <img src="{{ asset('assets/img/logo.png') }}?v={{ $assetVersion }}" alt="{{ config('app.name', 'CentralCorp Panel') }}" class="brand-logo mb-3">
                <h1 class="h4 fw-bold mb-1">@yield('heading', 'CentralCorp Panel')</h1>
                <p class="text-secondary small mb-0">@yield('subtitle', __('messages.install.subtitle'))</p>
            </div>

            <x-admin.flash />

            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    @yield('content')
                </div>
            </div>
        </div>
    </main>

    @stack('scripts')
</body>
</html>
