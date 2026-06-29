@extends('layouts.guest')

@section('title', __('messages.auth.confirm_password_title') . ' - ' . config('app.name', 'CentralCorp Panel'))
@section('subtitle', __('messages.auth.confirm_password_title'))

@section('content')
    <h2 class="h5 text-center fw-bold mb-2">{{ __('messages.auth.confirm_password_title') }}</h2>
    <p class="text-secondary text-center mb-4">{{ __('messages.auth.confirm_password_desc') }}</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div class="mb-4">
            <label for="password" class="form-label">{{ __('messages.auth.password') }}</label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-icon w-100">
            <i class="bi bi-shield-check"></i>
            {{ __('messages.auth.confirm_password_title') }}
        </button>

        @if (Route::has('password.request'))
            <div class="text-center mt-3">
                <a class="btn btn-link p-0" href="{{ route('password.request') }}">
                    {{ __('messages.auth.forgot_password') }}
                </a>
            </div>
        @endif
    </form>
@endsection
