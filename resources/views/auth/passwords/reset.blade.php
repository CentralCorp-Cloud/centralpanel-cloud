@extends('layouts.guest')

@section('title', __('messages.auth.new_password') . ' - ' . config('app.name', 'CentralCorp Panel'))
@section('subtitle', __('messages.auth.reset_password'))

@section('content')
    <h2 class="h5 text-center fw-bold mb-4">{{ __('messages.auth.new_password') }}</h2>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('messages.auth.email') }}</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('messages.auth.new_password') }}</label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password-confirm" class="form-label">{{ __('messages.auth.confirm_password') }}</label>
            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary btn-icon w-100">
            <i class="bi bi-key"></i>
            {{ __('messages.auth.reset_password') }}
        </button>
    </form>
@endsection
