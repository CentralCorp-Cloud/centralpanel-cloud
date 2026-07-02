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

<div class="card shadow-sm mt-4">
    <div class="card-header">
        <h2 class="h5 mb-0">{{ __('messages.update_page.cache_title') }}</h2>
    </div>
    <div class="card-body">
        <p class="text-secondary small mb-3">{{ __('messages.update_page.cache_description') }}</p>

        <div class="row g-3">
            @foreach([
                'all' => ['icon' => 'bi-stars', 'label' => __('messages.update_page.cache_all')],
                'views' => ['icon' => 'bi-window', 'label' => __('messages.update_page.cache_views')],
                'bootstrap' => ['icon' => 'bi-cpu', 'label' => __('messages.update_page.cache_bootstrap')],
                'application' => ['icon' => 'bi-database-x', 'label' => __('messages.update_page.cache_application')],
            ] as $target => $cacheAction)
                <div class="col-12 col-md-6 col-xl-3">
                    <form method="POST" action="{{ \Illuminate\Support\Facades\Route::has('admin.update.cache') ? route('admin.update.cache') : url('/admin/update/cache') }}" data-loading-label="{{ __('messages.common.loading') }}">
                        @csrf
                        <input type="hidden" name="target" value="{{ $target }}">
                        <button type="submit" class="btn btn-outline-primary btn-icon w-100">
                            <i class="bi {{ $cacheAction['icon'] }}"></i>
                            {{ $cacheAction['label'] }}
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
