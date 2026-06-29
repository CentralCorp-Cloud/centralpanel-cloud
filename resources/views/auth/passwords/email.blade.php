@extends('layouts.guest')

@section('title', __('messages.auth.reset_password') . ' - ' . config('app.name', 'CentralCorp Panel'))
@section('subtitle', __('messages.auth.reset_password'))

@section('content')
    <h2 class="h5 text-center fw-bold mb-4">{{ __('messages.auth.reset_password') }}</h2>

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('messages.auth.email') }}</label>
            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="btn btn-primary btn-icon w-100">
            <i class="bi bi-envelope-arrow-up"></i>
            {{ __('messages.auth.send_reset_link') }}
        </button>
    </form>
@endsection
