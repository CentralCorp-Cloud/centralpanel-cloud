@extends('layouts.guest')

@section('title', __('messages.auth.register') . ' - ' . config('app.name', 'CentralCorp Panel'))
@section('subtitle', __('messages.auth.register_title'))

@section('content')
    <h2 class="h5 text-center fw-bold mb-4">{{ __('messages.auth.register_title') }}</h2>

    <form method="POST" action="{{ route('register') }}">
        @csrf
        <div class="mb-3">
            <label for="name" class="form-label">{{ __('messages.auth.full_name') }}</label>
            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
            @error('name')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">{{ __('messages.auth.email') }}</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">{{ __('messages.auth.password') }}</label>
            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password-confirm" class="form-label">{{ __('messages.auth.password_confirm') }}</label>
            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-primary btn-icon w-100">
            <i class="bi bi-person-plus"></i>
            {{ __('messages.auth.register_btn') }}
        </button>
    </form>
@endsection
