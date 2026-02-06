@extends('layouts.admin')

@section('title', __('messages.ui.title'))

@section('content')
<div class="container-fluid px-4 py-3">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('messages.common.close') }}"></button>
        </div>
    @endif

    <h2 class="mb-4 text-3xl font-bold">{{ __('messages.ui.header') }}</h2>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form action="{{ route('admin.ui.update') }}" method="POST">
                @csrf

                {{-- Fieldset Alerte --}}
                <fieldset class="mb-4">
                    <legend class="fs-5 fw-bold mb-3">{{ __('messages.ui.alert_section') }}</legend>

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="alert_activation" value="0">
                        <input type="checkbox" id="alert_activation" name="alert_activation" class="form-check-input" value="1" {{ $uiOptions->alert_activation ? 'checked' : '' }}>
                        <label for="alert_activation" class="form-check-label">{{ __('messages.ui.alert_enable') }}</label>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="alert_scroll" value="0">
                        <input type="checkbox" id="alert_scroll" name="alert_scroll" class="form-check-input" value="1" {{ $uiOptions->alert_scroll ? 'checked' : '' }}>
                        <label for="alert_scroll" class="form-check-label">{{ __('messages.ui.alert_scroll') }}</label>
                    </div>

                    <div class="mb-3">
                        <label for="alert_msg" class="form-label">{{ __('messages.ui.alert_content') }}</label>
                        <input type="text" class="form-control" id="alert_msg" name="alert_msg" value="{{ $uiOptions->alert_msg }}" required>
                    </div>
                </fieldset>

                {{-- Fieldset Vidéo --}}
                <fieldset class="mb-4">
                    <legend class="fs-5 fw-bold mb-3">{{ __('messages.ui.video_section') }}</legend>

                    <div class="form-check form-switch mb-3">
                        <input type="hidden" name="video_activation" value="0">
                        <input type="checkbox" id="video_activation" name="video_activation" class="form-check-input" value="1" {{ $uiOptions->video_activation ? 'checked' : '' }}>
                        <label for="video_activation" class="form-check-label">{{ __('messages.ui.video_enable') }}</label>
                    </div>

                    <div class="mb-3">
                        <label for="video_url" class="form-label">{{ __('messages.ui.video_url') }}</label>
                        <input type="url" class="form-control" id="video_url" name="video_url" value="{{ $uiOptions->video_url }}" required>
                    </div>
                </fieldset>

                {{-- Fieldset Splash --}}
                <fieldset class="mb-4">
                    <legend class="fs-5 fw-bold mb-3">{{ __('messages.ui.splash_section') }}</legend>

                    <div class="mb-3">
                        <label for="splash" class="form-label">{{ __('messages.ui.splash_msg') }}</label>
                        <input type="text" class="form-control" id="splash" name="splash" value="{{ $uiOptions->splash }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="splash_author" class="form-label">{{ __('messages.ui.splash_author') }}</label>
                        <input type="text" class="form-control" id="splash_author" name="splash_author" value="{{ $uiOptions->splash_author }}" required>
                    </div>
                </fieldset>

                <button type="submit" class="btn btn-primary">{{ __('messages.common.update') }}</button>
            </form>
        </div>
    </div>

</div>
@endsection
