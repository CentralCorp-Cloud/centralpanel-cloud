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

            <div class="panel-muted-surface p-3 mb-4">
                <h2 class="panel-section-title">
                    <i class="bi bi-person-lock"></i>{{ __('messages.config.auth_mode') }}
                </h2>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="auth_mode" id="auth_azuriom"
                           value="azuriom"
                           @checked(old('auth_mode', $options->auth_mode ?? 'azuriom') === 'azuriom')>
                    <label class="form-check-label" for="auth_azuriom">
                        <strong>Azuriom</strong> — {{ __('messages.config.auth_azuriom_desc') }}
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="auth_mode" id="auth_microsoft"
                           value="microsoft"
                           @checked(old('auth_mode', $options->auth_mode ?? 'azuriom') === 'microsoft')>
                    <label class="form-check-label" for="auth_microsoft">
                        <strong>Microsoft</strong> — {{ __('messages.config.auth_microsoft_desc') }}
                    </label>
                </div>
            </div>

            <div id="azuriom-fields" class="panel-muted-surface p-3 mb-4">
                <h2 class="panel-section-title">
                    <i class="bi bi-cloud-arrow-down"></i>{{ __('messages.config.azuriom_settings') }}
                </h2>
                <div class="mb-3">
                    <label for="azuriom_url" class="form-label">{{ __('messages.config.azuriom_url') }}</label>
                    <input type="url" class="form-control" id="azuriom_url" name="azuriom_url"
                           placeholder="{{ __('messages.config.azuriom_url_placeholder') }}"
                           value="{{ old('azuriom_url', $options->azuriom_url ?? '') }}">
                </div>
                <div>
                    <label for="azuriom_api_key" class="form-label">{{ __('messages.config.azuriom_api_key') }}</label>
                    <input type="text" class="form-control" id="azuriom_api_key" name="azuriom_api_key"
                           placeholder="{{ __('messages.config.azuriom_api_key') }}"
                           value="{{ old('azuriom_api_key', $options->azuriom_api_key ?? '') }}">
                    <div class="form-text">{{ __('messages.config.api_key_desc') }}</div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-icon">
                <i class="bi bi-save"></i>
                {{ __('messages.common.save') }}
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const azuriomRadio = document.getElementById('auth_azuriom');
        const microsoftRadio = document.getElementById('auth_microsoft');
        const azuriomFields = document.getElementById('azuriom-fields');
        const azuriomUrl = document.getElementById('azuriom_url');
        const azuriomApiKey = document.getElementById('azuriom_api_key');

        const toggleAzuriomFields = () => {
            const enabled = azuriomRadio.checked;
            azuriomFields.hidden = !enabled;
            azuriomUrl.required = enabled;
            azuriomApiKey.required = enabled;
        };

        azuriomRadio.addEventListener('change', toggleAzuriomFields);
        microsoftRadio.addEventListener('change', toggleAzuriomFields);
        toggleAzuriomFields();
    });
</script>
@endsection
