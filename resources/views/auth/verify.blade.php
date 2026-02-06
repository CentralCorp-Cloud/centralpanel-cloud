@extends('layouts.app')

@section('content')
<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
    <div class="card shadow-lg border-0 w-100" style="max-width: 400px;">
        <div class="card-body p-5 text-center">
            <h2 class="mb-4 fw-bold text-primary">{{ __('messages.auth.verify_title') }}</h2>
            @if (session('resent'))
                <div class="alert alert-success" role="alert">
                    {{ __('messages.auth.verify_resent') }}
                </div>
            @endif
            <p class="mb-3">{{ __('messages.auth.verify_check_email') }}</p>
            <p class="mb-4">{{ __('messages.auth.verify_not_received') }}</p>
            <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                @csrf
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">{{ __('messages.auth.verify_resend') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
