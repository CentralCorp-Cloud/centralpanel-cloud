@extends('layouts.admin')

@section('title', 'Dossiers ignorés - ' . $instance->display_name)
@section('page-title', 'Dossiers ignorés : ' . $instance->display_name)

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.instances.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Retour aux instances
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="{{ route('admin.instances.ignore.store', $instance->id) }}" method="POST" class="mb-3">
                @csrf
                <div class="input-group">
                    <input type="text" class="form-control" name="folder_name" placeholder="Nom du dossier à ignorer"
                        required>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus"></i> Ajouter</button>
                </div>
            </form>
            <ul class="list-group">
                @forelse ($folders as $folder)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <code>{{ $folder->folder_name }}</code>
                        <form action="{{ route('admin.instances.ignore.destroy', [$instance->id, $folder->id]) }}"
                            method="POST">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </li>
                @empty
                    <li class="list-group-item text-muted">Aucun dossier ignoré</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
