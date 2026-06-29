<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'CentralCorp Panel'))</title>

    <script>
        (function() {
            document.documentElement.setAttribute('data-bs-theme', localStorage.getItem('theme') || 'dark');
        })();
    </script>

    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body class="auth-shell d-flex align-items-center justify-content-center py-4">
    <button class="btn btn-outline-secondary btn-square position-fixed top-0 end-0 m-3"
            id="themeToggle"
            type="button"
            data-bs-toggle="tooltip"
            data-theme-dark-label="{{ __('messages.navbar.theme_dark') }}"
            data-theme-light-label="{{ __('messages.navbar.theme_light') }}"
            aria-label="{{ __('messages.auth.change_theme') }}">
        <i id="themeIcon" class="bi bi-moon-stars"></i>
    </button>

    <main class="container">
        <div class="auth-card mx-auto">
            <div class="text-center mb-4">
                <a href="{{ url('/') }}" class="d-inline-flex mb-3">
                    <img src="{{ asset('assets/img/logo.png') }}" alt="{{ config('app.name', 'CentralCorp Panel') }}" class="brand-logo">
                </a>
                <h1 class="h4 fw-bold mb-1">{{ config('app.name', 'CentralCorp Panel') }}</h1>
                <p class="text-secondary small mb-0">@yield('subtitle', __('messages.auth.admin_panel'))</p>
            </div>

            <x-admin.flash />

            <div class="card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    @yield('content')
                </div>
            </div>

            <p class="text-secondary small text-center mt-4 mb-0">
                {{ __('messages.dashboard.copy_rights') }}
            </p>
        </div>
    </main>

    <script src="{{ asset('assets/js/panel.js') }}"></script>
</body>
</html>
