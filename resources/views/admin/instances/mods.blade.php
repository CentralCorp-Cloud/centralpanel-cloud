@extends('layouts.admin')

@section('title', 'Mods - ' . $instance->display_name)
@section('page-title', 'Mods optionnels : ' . $instance->display_name)

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

    <div class="row g-4">
        {{-- Available JAR files --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Fichiers JAR disponibles</h5>
                </div>
                <div class="card-body">
                    @if (count($modsData) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Fichier</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modsData as $mod)
                                        <tr>
                                            <td><code>{{ $mod['file'] }}</code></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-outline-primary"
                                                    onclick="openModForm('{{ $mod['file'] }}', '{{ addslashes($mod['name']) }}')">
                                                    <i class="bi bi-plus"></i> Optionnel
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">Aucun fichier JAR trouvé dans
                            <code>storage/app/public/data/{{ $instance->name }}/mods/</code>
                        </p>
                    @endif
                </div>
            </div>

            {{-- Add mod form (appears when clicking "+ Optionnel") --}}
            <div class="card shadow-sm mt-3" id="mod-add-card" style="display: none;">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Configurer le mod optionnel</h5>
                    <button type="button" class="btn-close" onclick="closeModForm()"></button>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.instances.mods.add', $instance->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="file" id="mod-file">

                        <div class="mb-3">
                            <label for="mod-name" class="form-label">Nom d'affichage</label>
                            <input type="text" class="form-control" id="mod-name" name="name" required
                                placeholder="Ex: OptiFine, Sodium...">
                        </div>

                        <div class="mb-3">
                            <label for="mod-description" class="form-label">Description</label>
                            <textarea class="form-control" id="mod-description" name="description" rows="2"
                                placeholder="Description courte du mod..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="mod-icon" class="form-label">Icône (optionnel)</label>
                            <input type="file" class="form-control" id="mod-icon" name="icon_file" accept="image/*">
                            <div class="form-text">Image carrée recommandée (64×64 ou 128×128)</div>
                        </div>

                        <div class="mb-3 form-check form-switch">
                            <input type="checkbox" class="form-check-input" id="mod-recommended" name="recommended"
                                value="1">
                            <label class="form-check-label" for="mod-recommended">Recommandé</label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Ajouter comme optionnel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Optional mods --}}
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Mods optionnels</h5>
                </div>
                <div class="card-body">
                    @forelse ($optionalMods as $mod)
                        <div class="card mb-2">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex gap-2 align-items-start">
                                        @if ($mod->icon)
                                            <img src="{{ asset('storage/' . $mod->icon) }}" alt=""
                                                style="width: 40px; height: 40px; object-fit: cover; border-radius: 6px;"
                                                class="flex-shrink-0">
                                        @endif
                                        <div>
                                            <strong>{{ $mod->name }}</strong>
                                            @if ($mod->recommended)
                                                <span class="badge bg-success ms-1">Recommandé</span>
                                            @endif
                                            <br><small class="text-muted">{{ $mod->file }}</small>
                                            @if ($mod->description)
                                                <p class="mb-0 mt-1 small">{{ $mod->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0">
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            onclick="editMod({{ $mod->id }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="{{ route('admin.instances.mods.delete', [$instance->id, $mod->id]) }}"
                                            method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i
                                                    class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Edit form (hidden, toggles on click) --}}
                                <div class="mt-3" id="edit-mod-{{ $mod->id }}" style="display: none;">
                                    <form action="{{ route('admin.instances.mods.update', [$instance->id, $mod->id]) }}"
                                        method="POST" enctype="multipart/form-data">
                                        @csrf @method('PUT')
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <input type="text" class="form-control form-control-sm" name="name"
                                                    value="{{ $mod->name }}" placeholder="Nom d'affichage">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="file" class="form-control form-control-sm" name="icon_file"
                                                    accept="image/*">
                                            </div>
                                            <div class="col-12">
                                                <textarea class="form-control form-control-sm" name="description" rows="2"
                                                    placeholder="Description">{{ $mod->description }}</textarea>
                                            </div>
                                            <div class="col-12 d-flex gap-2 align-items-center">
                                                <div class="form-check form-switch">
                                                    <input type="checkbox" class="form-check-input" name="recommended" value="1"
                                                        {{ $mod->recommended ? 'checked' : '' }}>
                                                    <label class="form-check-label">Recommandé</label>
                                                </div>
                                                <button type="submit" class="btn btn-sm btn-primary ms-auto">
                                                    <i class="bi bi-check"></i> Enregistrer
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">Aucun mod optionnel configuré</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModForm(file, name) {
            document.getElementById('mod-file').value = file;
            document.getElementById('mod-name').value = name.replace(/\.jar$/i, '');
            document.getElementById('mod-description').value = '';
            document.getElementById('mod-icon').value = '';
            document.getElementById('mod-recommended').checked = false;
            document.getElementById('mod-add-card').style.display = '';
            document.getElementById('mod-add-card').scrollIntoView({ behavior: 'smooth' });
        }

        function closeModForm() {
            document.getElementById('mod-add-card').style.display = 'none';
        }

        function editMod(id) {
            const el = document.getElementById('edit-mod-' + id);
            el.style.display = el.style.display === 'none' ? '' : 'none';
        }
    </script>
@endsection
