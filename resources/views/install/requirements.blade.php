@extends('layouts.install')

@section('title', 'Pré-requis - CentralCorp Panel')
@section('subtitle', 'Vérification des pré-requis')

@section('content')
@php $allMet = !in_array(false, $requirements, true); @endphp

<div class="panel-muted-surface p-3 mb-3">
    <h2 class="panel-section-title"><i class="bi bi-filetype-php"></i>Version PHP</h2>
    <div class="d-flex justify-content-between align-items-center">
        <span>PHP {{ \App\Http\Controllers\InstallController::MIN_PHP_VERSION }}+ <span class="text-primary">({{ \App\Http\Controllers\InstallController::parsePhpVersion() }})</span></span>
        <i class="bi {{ $requirements['php'] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }}"></i>
    </div>
</div>

<div class="panel-muted-surface p-3 mb-3">
    <h2 class="panel-section-title"><i class="bi bi-folder-check"></i>Permissions</h2>
    @foreach([
        'writable' => 'Dossier racine',
        'storage-writable' => 'Dossier storage',
        'bootstrap-writable' => 'Dossier bootstrap/cache',
    ] as $key => $label)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary-subtle">
            <span>{{ $label }}</span>
            <i class="bi {{ $requirements[$key] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }}"></i>
        </div>
    @endforeach
</div>

<div class="panel-muted-surface p-3 mb-4">
    <h2 class="panel-section-title"><i class="bi bi-puzzle"></i>Extensions PHP</h2>
    <div class="row g-2">
        @foreach(\App\Http\Controllers\InstallController::REQUIRED_EXTENSIONS as $extension)
            <div class="col-6">
                <div class="d-flex justify-content-between align-items-center p-2 rounded bg-body">
                    <span>{{ $extension }}</span>
                    <i class="bi {{ $requirements['extension-' . $extension] ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' }}"></i>
                </div>
            </div>
        @endforeach
    </div>
</div>

@if($allMet)
    <div class="alert alert-success">
        <i class="bi bi-check-circle me-2"></i>Tous les pré-requis sont satisfaits.
    </div>
    <a href="{{ route('install.database') }}" class="btn btn-primary btn-icon w-100">
        <i class="bi bi-arrow-right"></i>
        Continuer l'installation
    </a>
@else
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>Certains pré-requis ne sont pas satisfaits.
    </div>
    <button type="button" class="btn btn-warning btn-icon w-100" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise"></i>
        Vérifier à nouveau
    </button>
@endif
@endsection
