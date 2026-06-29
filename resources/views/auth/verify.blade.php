@extends('layouts.guest')

@section('title', __('messages.auth.verify_title') . ' - ' . config('app.name', 'CentralCorp Panel'))
@section('subtitle', __('messages.auth.verify_title'))

@section('content')
    <div class="text-center">
        <div class="panel-title-icon mx-auto mb-3"><i class="bi bi-envelope-check"></i></div>
        <h2 class="h5 fw-bold mb-3">{{ __('messages.auth.verify_title') }}</h2>

        @if (session('resent'))
            <div class="alert alert-success" role="alert">{{ __('messages.auth.verify_resent') }}</div>
        @endif

        <p class="text-secondary mb-2">{{ __('messages.auth.verify_check_email') }}</p>
        <p class="text-secondary mb-4">{{ __('messages.auth.verify_not_received') }}</p>

        <form method="POST" action="{{ route('verification.resend') }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-icon">
                <i class="bi bi-send"></i>
                {{ __('messages.auth.verify_resend') }}
            </button>
        </form>
    </div>
@endsection
