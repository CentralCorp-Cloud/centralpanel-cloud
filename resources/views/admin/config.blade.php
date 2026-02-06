@extends('layouts.admin')

@section('title', __('messages.config.title'))

@section('content')
    <div class="container-fluid p-0">
        <h2 class="text-3xl font-bold">{{ __('messages.config.header') }}</h2>
        
        @if(session('success'))
            <div class="alert alert-success" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card shadow mb-4">
            <div class="card-header">
                <h3 class="card-title">{{ __('messages.config.general_settings') }}</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.config.update') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="app_name">{{ __('messages.config.app_name') }}</label>
                        <input type="text" class="form-control" id="app_name" name="app_name" 
                               value="{{ config('app.name') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="azuriom_url" class="form-label">{{ __('messages.config.azuriom_url') }}</label>
                        <input type="text" class="form-control" id="azuriom_url" name="azuriom_url" 
                               placeholder="https://votre-site.azuriom.com" 
                               value="{{ $options->azuriom_url ?? '' }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="azuriom_api_key" class="form-label">{{ __('messages.config.azuriom_api_key') }}</label>
                        <input type="text" class="form-control" id="azuriom_api_key" name="azuriom_api_key" 
                               placeholder="Votre clé API Azuriom (plugin API Extender)" 
                               value="{{ $options->azuriom_api_key ?? '' }}" required>
                        <small class="form-text text-muted">{{ __('messages.config.api_key_desc') }}</small>
                    </div>

                    <button type="submit" class="btn btn-primary">{{ __('messages.common.save') }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection
