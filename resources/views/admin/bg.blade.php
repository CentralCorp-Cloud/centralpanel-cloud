@extends('layouts.admin')

@section('title', __('messages.bg.title'))

@section('content')
<x-admin.page-header :title="__('messages.bg.header')" :subtitle="__('messages.bg.subtitle')" icon="bi-image" />

@if(!$hasAzuriomApi)
    <div class="alert alert-warning" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>{{ __('messages.bg.config_required') }}</strong> {{ __('messages.bg.api_not_configured') }}
        <a href="{{ route('admin.config') }}" class="alert-link">{{ __('messages.bg.configure_api') }}</a>.
    </div>
@else
    <div class="row g-4">
        @foreach($roles as $role)
            @php $hasBackground = isset($backgrounds[$role['id']]); @endphp
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card role-bg-card h-100 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="h6 mb-0">{{ $role['name'] }}</h2>
                            @if($role['is_admin'])
                                <span class="badge text-bg-danger mt-1">Admin</span>
                            @endif
                        </div>
                        @if($hasBackground)
                            <form action="{{ route('admin.bg.destroy', $role['id']) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-square" data-confirm="{{ __('messages.bg.confirm_delete') }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="card-body d-flex flex-column">
                        @if($hasBackground)
                            <div class="role-bg-preview mb-3">
                                <img src="{{ asset('storage/' . $backgrounds[$role['id']]->image_path) }}"
                                     alt="Background pour {{ $role['name'] }}">
                            </div>
                        @else
                            <div class="role-bg-preview panel-muted-surface mb-3">
                                <i class="bi bi-image"></i>
                                <span>{{ __('messages.bg.no_image') }}</span>
                            </div>
                        @endif

                        <form action="{{ route('admin.bg.update') }}" method="POST" enctype="multipart/form-data" class="mt-auto">
                            @csrf
                            <input type="hidden" name="role_id" value="{{ $role['id'] }}">

                            <div class="mb-3">
                                <label for="bg_image_{{ $role['id'] }}" class="form-label">{{ __('messages.bg.choose_image') }}</label>
                                <input type="file"
                                       class="form-control"
                                       id="bg_image_{{ $role['id'] }}"
                                       name="bg_image"
                                       required
                                       onchange="updateFileName(this, 'file-name-{{ $role['id'] }}')">
                                <div class="form-text" id="file-name-{{ $role['id'] }}">{{ __('messages.common.no_file_selected') }}</div>
                            </div>

                            <button type="submit" class="btn btn-success btn-icon w-100">
                                <i class="bi bi-upload"></i>
                                {{ __('messages.common.update') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
@endsection

@push('scripts')
<script>
function updateFileName(input, targetId) {
    const fileName = input.files[0]?.name || '{{ __('messages.common.no_file_selected') }}';
    document.getElementById(targetId).textContent = fileName;
}
</script>
@endpush
