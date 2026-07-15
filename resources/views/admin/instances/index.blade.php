@extends('layouts.admin')

@section('title', 'Instances')
@section('page-title', 'Gestion des instances')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <p class="text-muted mb-0">Gérez vos instances de jeu (serveur, loader, mods, whitelist, etc.)</p>
        <a href="{{ route('admin.instances.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Nouvelle instance
        </a>
    </div>

    @php $indexAuthMode = \App\Models\OptionsGeneral::first()?->auth_mode ?? 'azuriom'; @endphp
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        @foreach ($instances as $instance)
            <div class="col">
                <div class="card h-100 shadow-sm {{ $instance->is_default ? 'border-primary' : '' }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0">
                                {{ $instance->display_name }}
                                @if ($instance->is_default)
                                    <span class="badge bg-primary ms-1">Par défaut</span>
                                @endif
                            </h5>
                        </div>
                        <p class="text-muted small mb-2"><code>{{ $instance->name }}</code></p>
                        @if ($instance->description)
                            <p class="small mb-3">{{ \Illuminate\Support\Str::limit($instance->description, 140) }}</p>
                        @endif

                        <div class="mb-3">
                            @if ($instance->server_ip)
                                <small class="d-block"><i class="bi bi-hdd me-1"></i>
                                    {{ $instance->server_ip }}{{ $instance->server_port ? ':' . $instance->server_port : '' }}</small>
                            @endif
                            @if ($instance->minecraft_version)
                                <small class="d-block"><i class="bi bi-controller me-1"></i> MC
                                    {{ $instance->minecraft_version }}</small>
                            @endif
                            @if ($instance->loader_type && $instance->loader_activation)
                                <small class="d-block"><i class="bi bi-cloud-arrow-down me-1"></i>
                                    {{ ucfirst($instance->loader_type) }} {{ $instance->loader_build_version }}</small>
                            @endif
                        </div>

                        <div class="d-flex flex-wrap gap-1 mb-3">
                            <a href="{{ route('admin.instances.mods', $instance->id) }}"
                                class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-box me-1"></i> Mods
                            </a>
                            <a href="{{ route('admin.instances.whitelist', $instance->id) }}"
                                class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-list-check me-1"></i> Whitelist
                            </a>
                            <a href="{{ route('admin.instances.ignore', $instance->id) }}"
                                class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-slash me-1"></i> Ignorés
                            </a>
                            @if ($indexAuthMode === 'azuriom')
                                <a href="{{ route('admin.instances.bg', $instance->id) }}" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-image me-1"></i> Backgrounds
                                </a>
                            @endif
                            <a href="{{ route('admin.instances.files', $instance->id) }}"
                                class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-folder me-1"></i> Fichiers
                            </a>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent d-flex justify-content-between">
                        <div>
                            <a href="{{ route('admin.instances.edit', $instance->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Éditer
                            </a>
                            @if (!$instance->is_default)
                                <form action="{{ route('admin.instances.setDefault', $instance->id) }}" method="POST"
                                    class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-star"></i> Défaut
                                    </button>
                                </form>
                            @endif
                        </div>
                        @if (!$instance->is_default)
                            <form action="{{ route('admin.instances.destroy', $instance->id) }}" method="POST"
                                onsubmit="return confirm('Supprimer cette instance ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
