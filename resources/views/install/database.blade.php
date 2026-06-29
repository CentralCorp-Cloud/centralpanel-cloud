@extends('layouts.install')

@section('title', __('messages.install.title'))
@section('subtitle', 'Assistant d’installation')

@section('content')
<form method="POST" action="{{ route('install.store') }}" id="installForm" data-loading-label="Installation...">
    @csrf

    <div class="panel-muted-surface p-3 mb-4">
        <h2 class="panel-section-title"><i class="bi bi-database"></i>Base de données</h2>

        <div class="alert alert-primary py-2 small mb-3">
            <i class="bi bi-lightbulb me-1"></i>
            SQLite est recommandé pour une installation rapide.
        </div>

        <div class="mb-3">
            <label for="type" class="form-label">Type de base de données</label>
            <select class="form-select" id="type" name="type" required>
                <option value="sqlite" {{ old('type', 'sqlite') == 'sqlite' ? 'selected' : '' }}>SQLite (recommandé)</option>
                @foreach($databaseDrivers as $key => $name)
                    @if($key !== 'sqlite')
                        <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>{{ $name }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        <div id="database-config" class="d-none">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="host" class="form-label">Hôte</label>
                    <input type="text" class="form-control" id="host" name="host" value="{{ old('host', 'localhost') }}">
                </div>
                <div class="col-md-4">
                    <label for="port" class="form-label">Port</label>
                    <input type="number" class="form-control" id="port" name="port" value="{{ old('port', '3306') }}">
                </div>
                <div class="col-12">
                    <label for="database" class="form-label">Nom de la base</label>
                    <input type="text" class="form-control" id="database" name="database" value="{{ old('database') }}" placeholder="centralcorp_panel">
                </div>
                <div class="col-md-6">
                    <label for="user" class="form-label">Utilisateur</label>
                    <input type="text" class="form-control" id="user" name="user" value="{{ old('user') }}" placeholder="root">
                </div>
                <div class="col-md-6">
                    <label for="db_password" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="db_password" name="db_password">
                </div>
            </div>
        </div>
    </div>

    <div class="panel-muted-surface p-3 mb-4">
        <h2 class="panel-section-title"><i class="bi bi-person-badge"></i>Compte administrateur</h2>

        <div class="mb-3">
            <label for="name" class="form-label">Nom d'utilisateur</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required placeholder="admin">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required placeholder="admin@example.com">
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label for="password" class="form-label">Mot de passe</label>
                <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
            </div>
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Confirmation</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required placeholder="••••••••">
            </div>
        </div>
        <div class="form-text">Minimum 8 caractères.</div>
    </div>

    <button type="submit" class="btn btn-primary btn-icon w-100">
        <i class="bi bi-rocket-takeoff"></i>
        Installer CentralCorp Panel
    </button>
</form>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/panel.js') }}"></script>
<script>
    const typeSelect = document.getElementById('type');
    const dbConfig = document.getElementById('database-config');

    function toggleDbConfig() {
        dbConfig.classList.toggle('d-none', typeSelect.value === 'sqlite');
    }

    typeSelect.addEventListener('change', toggleDbConfig);
    toggleDbConfig();
</script>
@endpush
