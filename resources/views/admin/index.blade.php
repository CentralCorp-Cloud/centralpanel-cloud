@extends('layouts.admin')

@section('title', __('messages.dashboard.title'))

@section('page-title', __('messages.dashboard.welcome'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <!-- Statistiques -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.dashboard.stats') }}</h5>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-0">{{ __('messages.dashboard.account_count') }}</h6>
                            <h2 class="mb-0">{{ $userCount ?? 0 }}</h2>
                        </div>
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes de version -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.dashboard.release_notes') }}</h5>
                    <div class="list-group">
                        @if(isset($releases) && count($releases) > 0)
                            @foreach($releases as $release)
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <a href="{{ $release->link }}" target="_blank" class="text-decoration-none">
                                                {{ __('messages.dashboard.version') }} {{ $release->title }}
                                            </a>
                                        </h6>
                                        <small class="text-muted">{{ $release->date }}</small>
                                    </div>
                                    <p class="mb-1">{{ $release->description }}</p>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted">{{ __('messages.dashboard.no_notes') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Export/Import -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">{{ __('messages.dashboard.export_import') }}</h5>
                    <div class="d-flex flex-column gap-3">
                        <div>
                            <h6 class="mb-2">{{ __('messages.dashboard.export_settings') }}</h6>
                            <p class="text-muted small mb-2">{{ __('messages.dashboard.export_desc') }}</p>
                            <a href="{{ route('admin.settings.export') }}" class="btn btn-primary">
                                <i class="fas fa-download me-2"></i>{{ __('messages.dashboard.export_btn') }}
                            </a>
                        </div>
                        <div>
                            <h6 class="mb-2">{{ __('messages.dashboard.import_settings') }}</h6>
                            <p class="text-muted small mb-2">{{ __('messages.dashboard.import_desc') }}</p>
                            <form action="{{ route('admin.settings.import') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="input-group">
                                    <input type="file" class="form-control" name="settings_file" accept=".centralcorp" required>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-upload me-2"></i>{{ __('messages.dashboard.import_btn') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show position-fixed bottom-0 end-0 m-3" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show position-fixed bottom-0 end-0 m-3" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
@endsection

