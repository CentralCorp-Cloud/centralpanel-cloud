@extends('layouts.admin')

@section('title', __('messages.instances.ignore.title', ['name' => $instance->display_name]))
@section('page-title', __('messages.instances.ignore.header', ['name' => $instance->display_name]))

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.instances.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> {{ __('messages.instances.back') }}
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.instances.ignore.store', $instance->id) }}" method="POST" class="mb-3">
                @csrf
                <div class="input-group">
                    <input type="text" class="form-control" name="folder_name" placeholder="{{ __('messages.instances.ignore.placeholder') }}"
                        required>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus"></i> {{ __('messages.instances.ignore.add') }}</button>
                </div>
            </form>
            <ul class="list-group">
                @forelse ($folders as $folder)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <code>{{ $folder->folder_name }}</code>
                        <form action="{{ route('admin.instances.ignore.destroy', [$instance->id, $folder->id]) }}"
                            method="POST">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </li>
                @empty
                    <li class="list-group-item text-muted">{{ __('messages.instances.ignore.none') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
