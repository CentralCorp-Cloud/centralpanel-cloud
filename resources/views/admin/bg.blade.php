@extends('layouts.admin')

@section('title', __('messages.bg.title'))

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 fw-bold">{{ __('messages.bg.header') }}</h2>
            <p class="text-muted mb-0">{{ __('messages.bg.subtitle') }}</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('messages.common.close') }}"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!$hasAzuriomApi)
        <div class="alert alert-warning" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>{{ __('messages.bg.config_required') }}</strong> {{ __('messages.bg.api_not_configured') }} 
            <a href="{{ route('admin.general') }}" class="alert-link">{{ __('messages.bg.configure_api') }}</a>.
        </div>
    @else
        <div class="row g-4">
        @foreach($roles as $role)
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    @php
                        $hasBackground = isset($backgrounds[$role['id']]);
                    @endphp
                    @if($role['is_admin'])
                        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ $role['name'] }}</h5>
                            @if($hasBackground)
                                <form action="{{ route('admin.bg.destroy', $role['id']) }}" method="POST" onsubmit="return confirm('{{ __('messages.bg.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-light border-0">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @else
                        <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ $role['name'] }}</h5>
                            @if($hasBackground)
                                <form action="{{ route('admin.bg.destroy', $role['id']) }}" method="POST" onsubmit="return confirm('{{ __('messages.bg.confirm_delete') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-light border-0">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif

                    <div class="card-body">
                        @if($hasBackground)
                            <div class="mb-3">
                                <img src="{{ asset('storage/' . $backgrounds[$role['id']]->image_path) }}"
                                     class="img-fluid rounded"
                                     alt="Background pour {{ $role['name'] }}">
                            </div>
                        @else
                            <div class="bg-light d-flex align-items-center justify-content-center rounded mb-3" style="height: 180px;">
                                <span class="text-muted">{{ __('messages.bg.no_image') }}</span>
                            </div>
                        @endif

                        <form action="{{ route('admin.bg.update') }}" method="POST" enctype="multipart/form-data">
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

                            <button type="submit" class="btn btn-success w-100 d-flex align-items-center justify-content-center">
                                <i class="bi bi-upload me-2"></i>
                                {{ __('messages.common.update') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
        </div>
    @endif
</div>

@push('scripts')
<script>
function updateFileName(input, targetId) {
    const fileName = input.files[0]?.name || '{{ __('messages.common.no_file_selected') }}';
    document.getElementById(targetId).textContent = fileName;
}
</script>
@endpush
@endsection
