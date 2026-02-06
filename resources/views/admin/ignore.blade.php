@extends('layouts.admin')

@section('title', __('messages.ignore.title'))

@section('content')
<div class="container-fluid px-4 py-3">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('messages.common.close') }}"></button>
        </div>
    @endif

    <h2 class="mb-4">{{ __('messages.ignore.add_header') }}</h2>
    <div class="card shadow-sm border-0 mb-5">
        <div class="card-body">
            <form action="{{ route('admin.ignore.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="ignored_folders" class="form-label fw-semibold">{{ __('messages.ignore.paths_label') }} <span class="text-muted">{{ __('messages.ignore.paths_hint') }}</span></label>
                    <input type="text" class="form-control" id="ignored_folders" name="ignored_folders" placeholder="{{ __('messages.ignore.paths_placeholder') }}">
                </div>
                <button type="submit" class="btn btn-primary">{{ __('messages.common.save') }}</button>
            </form>
        </div>
    </div>

    <h2 class="mb-3">{{ __('messages.ignore.current_header') }}</h2>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if($folders->isEmpty())
                <p class="text-muted">{{ __('messages.ignore.none_ignored') }}</p>
            @else
                <ul class="list-group">
                    @foreach($folders as $folder)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-break">{{ $folder->folder_name }}</span>
                            <form action="{{ route('admin.ignore.destroyFolder', $folder->id) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">{{ __('messages.common.delete') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

</div>
@endsection
