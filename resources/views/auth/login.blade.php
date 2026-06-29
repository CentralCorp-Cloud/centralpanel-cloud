@extends('layouts.guest')

@section('title', __('messages.auth.login') . ' - ' . config('app.name', 'CentralCorp Panel'))
@section('subtitle', __('messages.auth.admin_panel'))

@section('content')
    <h2 class="h5 text-center fw-bold mb-1">{{ __('messages.auth.login_title') }}</h2>
    <p class="text-secondary small text-center mb-4">{{ __('messages.auth.login_subtitle') }}</p>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label small">{{ __('messages.auth.email_address') }}</label>
            <input type="email"
                   class="form-control @error('email') is-invalid @enderror"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autocomplete="email"
                   autofocus
                   placeholder="admin@example.com">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label small">{{ __('messages.auth.password') }}</label>
            <input type="password"
                   class="form-control @error('password') is-invalid @enderror"
                   id="password"
                   name="password"
                   required
                   autocomplete="current-password"
                   placeholder="••••••••">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 gap-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label small" for="remember">{{ __('messages.auth.remember_me') }}</label>
            </div>
            @if (Route::has('password.request'))
                <a class="small text-primary text-decoration-none" href="{{ route('password.request') }}">
                    {{ __('messages.auth.forgot_password') }}
                </a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary btn-icon w-100">
            <i class="bi bi-box-arrow-in-right"></i>
            {{ __('messages.auth.login_btn') }}
        </button>
    </form>
@endsection
