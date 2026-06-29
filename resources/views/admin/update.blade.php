@extends('layouts.admin')

@section('title', __('messages.update_page.title'))
@section('page-title', __('messages.update_page.header'))

@section('content')
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="panel-muted-surface p-3 h-100">
                    <div class="text-secondary small">{{ __('messages.update_page.current_version') }}</div>
                    <div class="h4 mb-0">{{ $currentVersion }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel-muted-surface p-3 h-100">
                    <div class="text-secondary small">{{ __('messages.update_page.latest_version') }}</div>
                    <div class="h4 mb-0">{{ $info['version'] ?? '?' }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel-muted-surface p-3 h-100">
                    <div class="text-secondary small">{{ __('messages.update_page.php_required') }}</div>
                    <div class="h4 mb-0">{{ $info['php_version'] ?? '?' }}</div>
                </div>
            </div>
        </div>

        @unless($info)
            <div class="alert alert-warning mt-4 mb-0">{{ __('messages.update_page.unable_fetch') }}</div>
        @endunless
    </div>
</div>

@if($hasUpdate)
    <form method="POST" action="{{ route('admin.update.run') }}" data-loading-label="{{ __('messages.common.loading') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-icon">
            <i class="bi bi-cloud-arrow-up"></i>
            {{ __('messages.update_page.update_now') }}
        </button>
    </form>
@else
    <div class="alert alert-info">{{ __('messages.update_page.no_update') }}</div>
@endif
@endsection
