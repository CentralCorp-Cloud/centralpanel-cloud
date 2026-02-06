@extends('layouts.app')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="card shadow-lg border-0 w-100" style="max-width: 400px;">
        <div class="card-body p-5">
            <h2 class="mb-4 text-center fw-bold text-primary">{{ __('messages.auth.confirm_password_title') }}</h2>
            <p class="mb-4 text-center">{{ __('messages.auth.confirm_password_desc') }}</p>
            <form method="POST" action="{{ route('password.confirm') }}">
                @csrf
                <div class="mb-3">
                    <label for="password" class="form-label">{{ __('messages.auth.password') }}</label>
                    <input id="password" type="password" class="form-control rounded-pill @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">
                    @error('password')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold mb-2">
                    {{ __('messages.auth.confirm_password_title') }}
                </button>
                @if (Route::has('password.request'))
                    <div class="text-center mt-2">
                        <a class="btn btn-link p-0" href="{{ route('password.request') }}">
                            {{ __('messages.auth.forgot_password') }}
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
