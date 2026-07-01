@extends('layouts.admin')

@section('title', __('messages.dashboard.title'))
@section('page-title', __('messages.dashboard.welcome'))

@section('content')
<div class="row g-4">
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <h2 class="h5 mb-0">{{ __('messages.dashboard.stats_overview') }}</h2>
            </div>
            <div class="card-body dashboard-summary">
                <div class="dashboard-primary-stat">
                    <div>
                        <p class="text-secondary mb-1">{{ __('messages.dashboard.account_count') }}</p>
                        <div class="display-6 fw-bold">{{ $userCount ?? 0 }}</div>
                    </div>
                    <span class="panel-title-icon"><i class="bi bi-people"></i></span>
                </div>

                <div class="dashboard-stat-grid">
                    @foreach(($stats['counts'] ?? []) as $stat)
                        <div class="dashboard-stat-tile">
                            <span class="dashboard-stat-icon"><i class="bi {{ $stat['icon'] }}"></i></span>
                            <div>
                                <div class="dashboard-stat-value">{{ $stat['value'] }}</div>
                                <div class="dashboard-stat-label">{{ $stat['label'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="dashboard-status-panel">
                    <div class="dashboard-status-title">{{ __('messages.dashboard.quick_status') }}</div>
                    <div class="dashboard-status-list">
                        @foreach(($stats['status'] ?? []) as $status)
                            <div class="dashboard-status-row">
                                <span>{{ $status['label'] }}</span>
                                <span class="badge {{ $status['enabled'] ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $status['enabled'] ? __('messages.common.enabled') : __('messages.common.disabled') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-8">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <h2 class="h5 mb-0">{{ __('messages.dashboard.release_notes') }}</h2>
            </div>
            <div class="card-body">
                @if(isset($releases) && count($releases) > 0)
                    <div class="panel-list dashboard-release-list">
                        @foreach($releases as $release)
                            <a href="{{ $release->link }}" target="_blank" rel="noopener noreferrer" class="panel-list-item panel-list-link">
                                <div class="d-flex w-100 justify-content-between gap-3">
                                    <h3 class="h6 mb-1">{{ __('messages.dashboard.version') }} {{ $release->title }}</h3>
                                    @if($release->date)
                                        <small class="text-secondary text-nowrap">{{ $release->date }}</small>
                                    @endif
                                </div>
                                <p class="text-secondary small mb-0">{{ \Illuminate\Support\Str::limit($release->description, 220) }}</p>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bi bi-journal-text"></i>
                        <span>{{ __('messages.dashboard.no_notes') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h2 class="h5 mb-0">{{ __('messages.dashboard.export_import') }}</h2>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-12 col-lg-5">
                        <h3 class="h6">{{ __('messages.dashboard.export_settings') }}</h3>
                        <p class="text-secondary small">{{ __('messages.dashboard.export_desc') }}</p>
                        <a href="{{ route('admin.settings.export') }}" class="btn btn-primary btn-icon">
                            <i class="bi bi-download"></i>
                            {{ __('messages.dashboard.export_btn') }}
                        </a>
                    </div>
                    <div class="col-12 col-lg-7">
                        <h3 class="h6">{{ __('messages.dashboard.import_settings') }}</h3>
                        <p class="text-secondary small">{{ __('messages.dashboard.import_desc') }}</p>
                        <form action="{{ route('admin.settings.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="input-group">
                                <input type="file" class="form-control" name="settings_file" accept=".centralcorp,.json" required>
                                <button type="submit" class="btn btn-success btn-icon">
                                    <i class="bi bi-upload"></i>
                                    {{ __('messages.dashboard.import_btn') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
