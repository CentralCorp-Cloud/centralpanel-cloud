@extends('layouts.admin')

@section('title', __('messages.rpc.title'))
@section('page-title', __('messages.rpc.title'))

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>{{ __('messages.common.errors_occurred') }}</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('admin.rpc.update') }}" method="POST">
                @csrf

                <fieldset class="border p-3 mb-4 rounded">
                    <legend class="float-none w-auto px-2">{{ __('messages.rpc.activation') }}</legend>
                    <div class="form-check form-switch mb-2">
                        <input type="hidden" name="rpc_activation" value="0">
                        <input type="checkbox" id="rpc_activation" name="rpc_activation" class="form-check-input" value="1"
                               {{ old('rpc_activation', optional($rpcOptions)->rpc_activation) ? 'checked' : '' }}>
                        <label for="rpc_activation" class="form-check-label">{{ __('messages.rpc.enable_rpc') }}</label>
                    </div>
                </fieldset>

                <fieldset class="border p-3 mb-4 rounded">
                    <legend class="float-none w-auto px-2">{{ __('messages.rpc.general_info') }}</legend>

                    <div class="mb-3">
                        <label for="rpc_id" class="form-label">{{ __('messages.rpc.client_id') }}</label>
                        <input type="text" class="form-control" id="rpc_id" name="rpc_id"
                               value="{{ old('rpc_id', optional($rpcOptions)->rpc_id) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="rpc_details" class="form-label">{{ __('messages.rpc.details') }}</label>
                        <input type="text" class="form-control" id="rpc_details" name="rpc_details"
                               value="{{ old('rpc_details', optional($rpcOptions)->rpc_details) }}" required>
                    </div>

                    <div class="mb-3">
                        <label for="rpc_state" class="form-label">{{ __('messages.rpc.state') }}</label>
                        <input type="text" class="form-control" id="rpc_state" name="rpc_state"
                               value="{{ old('rpc_state', optional($rpcOptions)->rpc_state) }}" required>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="rpc_large_text" class="form-label">{{ __('messages.rpc.large_image_text') }}</label>
                            <input type="text" class="form-control" id="rpc_large_text" name="rpc_large_text"
                                   value="{{ old('rpc_large_text', optional($rpcOptions)->rpc_large_text) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="rpc_small_text" class="form-label">{{ __('messages.rpc.small_image_text') }}</label>
                            <input type="text" class="form-control" id="rpc_small_text" name="rpc_small_text"
                                   value="{{ old('rpc_small_text', optional($rpcOptions)->rpc_small_text) }}" required>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="border p-3 mb-4 rounded">
                    <legend class="float-none w-auto px-2">{{ __('messages.rpc.custom_buttons') }}</legend>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="rpc_button1" class="form-label">{{ __('messages.rpc.button1_name') }}</label>
                            <input type="text" class="form-control" id="rpc_button1" name="rpc_button1"
                                   value="{{ old('rpc_button1', optional($rpcOptions)->rpc_button1) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="rpc_button1_url" class="form-label">{{ __('messages.rpc.button1_url') }}</label>
                            <input type="url" class="form-control" id="rpc_button1_url" name="rpc_button1_url"
                                   value="{{ old('rpc_button1_url', optional($rpcOptions)->rpc_button1_url) }}">
                        </div>

                        <div class="col-md-6">
                            <label for="rpc_button2" class="form-label">{{ __('messages.rpc.button2_name') }}</label>
                            <input type="text" class="form-control" id="rpc_button2" name="rpc_button2"
                                   value="{{ old('rpc_button2', optional($rpcOptions)->rpc_button2) }}">
                        </div>
                        <div class="col-md-6">
                            <label for="rpc_button2_url" class="form-label">{{ __('messages.rpc.button2_url') }}</label>
                            <input type="url" class="form-control" id="rpc_button2_url" name="rpc_button2_url"
                                   value="{{ old('rpc_button2_url', optional($rpcOptions)->rpc_button2_url) }}">
                        </div>
                    </div>
                </fieldset>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">💾 {{ __('messages.rpc.update_btn') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
