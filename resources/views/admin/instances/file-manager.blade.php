@extends('layouts.admin')

@section('title', __('messages.instances.files.title', ['name' => $instance->display_name]))
@section('page-title', __('messages.instances.files.header', ['name' => $instance->display_name]))

@section('content')
    <div class="mb-3">
        <a href="{{ route('admin.instances.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> {{ __('messages.instances.back') }}
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-folder me-2"></i>{{ __('messages.instances.files.manager', ['name' => $instance->display_name]) }}</h5>
            <small class="text-muted">{{ __('messages.instances.files.folder') }} <code>data/{{ $instance->name }}</code></small>
        </div>
        <div class="card-body p-0">
            <div id="fm" style="height: min(760px, calc(100vh - 250px));"></div>
        </div>
    </div>
@endsection
