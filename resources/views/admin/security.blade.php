@extends('layouts.admin')

@section('title', __('messages.security.title'))
@section('page-title', __('messages.security.header'))

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.security.update') }}" method="POST">
            @csrf

            <div class="panel-muted-surface p-3 mb-4">
                <div class="form-check form-switch">
                    <input type="hidden" name="maintenance" value="0">
                    <input type="checkbox"
                           class="form-check-input"
                           id="maintenance"
                           name="maintenance"
                           value="1"
                           {{ old('maintenance', $securityOptions->maintenance) ? 'checked' : '' }}>
                    <label class="form-check-label fw-semibold" for="maintenance">
                        {{ __('messages.security.maintenance_enable') }}
                    </label>
                    <div class="form-text">{{ __('messages.security.maintenance_desc') }}</div>
                </div>
            </div>

            <div class="mb-4">
                <label for="maintenance_message" class="form-label">{{ __('messages.security.maintenance_msg') }}</label>
                <input type="text"
                       class="form-control"
                       id="maintenance_message"
                       name="maintenance_message"
                       value="{{ old('maintenance_message', $securityOptions->maintenance_message) }}"
                       required
                       placeholder="{{ __('messages.security.maintenance_placeholder') }}">
            </div>

            <button type="submit" class="btn btn-primary btn-icon">
                <i class="bi bi-save"></i>
                {{ __('messages.common.update') }}
            </button>
        </form>
    </div>
</div>
@endsection
