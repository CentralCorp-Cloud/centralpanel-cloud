@extends('layouts.admin')

@section('title', __('messages.config.title'))
@section('page-title', __('messages.config.header'))

@section('content')
<div class="card shadow-sm">
    <div class="card-header">
        <h2 class="h5 mb-0">{{ __('messages.config.general_settings') }}</h2>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.config.update') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="app_name" class="form-label">{{ __('messages.config.app_name') }}</label>
                <input type="text" class="form-control" id="app_name" name="app_name"
                       value="{{ old('app_name', config('app.name')) }}" required>
            </div>
            <div class="mb-3">
                <label for="azuriom_url" class="form-label">{{ __('messages.config.azuriom_url') }}</label>
                <input type="url" class="form-control" id="azuriom_url" name="azuriom_url"
                       placeholder="https://votre-site.azuriom.com"
                       value="{{ old('azuriom_url', $options->azuriom_url ?? '') }}" required>
            </div>
            <div class="mb-4">
                <label for="azuriom_api_key" class="form-label">{{ __('messages.config.azuriom_api_key') }}</label>
                <input type="text" class="form-control" id="azuriom_api_key" name="azuriom_api_key"
                       placeholder="Votre clé API Azuriom"
                       value="{{ old('azuriom_api_key', $options->azuriom_api_key ?? '') }}" required>
                <div class="form-text">{{ __('messages.config.api_key_desc') }}</div>
            </div>

            <button type="submit" class="btn btn-primary btn-icon">
                <i class="bi bi-save"></i>
                {{ __('messages.common.save') }}
            </button>
        </form>
    </div>
</div>
@endsection
