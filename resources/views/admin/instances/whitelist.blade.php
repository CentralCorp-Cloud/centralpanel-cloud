@extends('layouts.admin')

@section('title', 'Whitelist - ' . $instance->display_name)
@section('page-title', 'Whitelist : ' . $instance->display_name)

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
        {{-- ========================================= --}}
        {{-- Users section --}}
        {{-- ========================================= --}}
        <div class="{{ $authMode === 'microsoft' ? 'col-12' : 'col-md-6' }}">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Utilisateurs whitelist</h5>
                    @if ($authMode === 'azuriom' && ($hasAzuriomApi ?? false))
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-refresh-users">
                            <i class="bi bi-arrow-clockwise me-1"></i> Rafraîchir
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    @if ($authMode === 'azuriom' && ($hasAzuriomApi ?? false))
                        {{-- Azuriom mode: search + checkbox selection --}}
                        <div class="mb-3">
                            <input type="text" class="form-control" id="search-users"
                                placeholder="Rechercher un utilisateur...">
                        </div>
                        <form action="{{ route('admin.instances.whitelist.store', $instance->id) }}" method="POST"
                            id="form-add-users">
                            @csrf
                            <div id="azuriom-users-list" style="max-height: 300px; overflow-y: auto;" class="mb-3">
                                <div class="text-center text-muted py-3">
                                    <div class="spinner-border spinner-border-sm me-1"></div> Chargement...
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="btn-add-selected-users" disabled>
                                <i class="bi bi-plus-circle me-1"></i> Ajouter les sélectionnés
                            </button>
                        </form>
                    @else
                        {{-- Microsoft mode: manual input --}}
                        <form action="{{ route('admin.instances.whitelist.store', $instance->id) }}" method="POST" class="mb-3">
                            @csrf
                            <div class="input-group">
                                <input type="text" class="form-control" name="username" placeholder="Nom d'utilisateur"
                                    required>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-plus"></i> Ajouter</button>
                            </div>
                        </form>
                    @endif

                    {{-- Current whitelisted users --}}
                    <h6 class="mt-3 mb-2">Utilisateurs actuels ({{ $users->count() }})</h6>
                    <ul class="list-group">
                        @forelse ($users as $user)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $user->users }}
                                <form action="{{ route('admin.instances.whitelist.destroyUser', [$instance->id, $user->id]) }}"
                                    method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i
                                            class="bi bi-trash"></i></button>
                                </form>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">Aucun utilisateur</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- ========================================= --}}
        {{-- Roles section (Azuriom only) --}}
        {{-- ========================================= --}}
        @if ($authMode === 'azuriom')
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Rôles whitelist</h5>
                        @if ($hasAzuriomApi ?? false)
                            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-refresh-roles">
                                <i class="bi bi-arrow-clockwise me-1"></i> Rafraîchir
                            </button>
                        @endif
                    </div>
                    <div class="card-body">
                        @if ($hasAzuriomApi ?? false)
                            {{-- Azuriom mode: search + checkbox selection --}}
                            <div class="mb-3">
                                <input type="text" class="form-control" id="search-roles" placeholder="Rechercher un rôle...">
                            </div>
                            <form action="{{ route('admin.instances.whitelist.store', $instance->id) }}" method="POST"
                                id="form-add-roles">
                                @csrf
                                <div id="azuriom-roles-list" style="max-height: 300px; overflow-y: auto;" class="mb-3">
                                    <div class="text-center text-muted py-3">
                                        <div class="spinner-border spinner-border-sm me-1"></div> Chargement...
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100" id="btn-add-selected-roles" disabled>
                                    <i class="bi bi-plus-circle me-1"></i> Ajouter les sélectionnés
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.instances.whitelist.store', $instance->id) }}" method="POST" class="mb-3">
                                @csrf
                                <div class="input-group">
                                    <input type="text" class="form-control" name="role" placeholder="Nom du rôle" required>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-plus"></i> Ajouter</button>
                                </div>
                            </form>
                        @endif

                        {{-- Current whitelisted roles --}}
                        <h6 class="mt-3 mb-2">Rôles actuels ({{ $roles->count() }})</h6>
                        <ul class="list-group">
                            @forelse ($roles as $role)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    {{ $role->role }}
                                    <form action="{{ route('admin.instances.whitelist.destroyRole', [$instance->id, $role->id]) }}"
                                        method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i
                                                class="bi bi-trash"></i></button>
                                    </form>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">Aucun rôle</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if ($authMode === 'azuriom' && ($hasAzuriomApi ?? false))
        <script>
            let cachedUsers = null;
            let cachedRoles = null;

            // ======= USERS =======
            function fetchUsers(forceRefresh = false) {
                const container = document.getElementById('azuriom-users-list');
                if (!container) return;

                if (cachedUsers && !forceRefresh) {
                    renderUsers(cachedUsers);
                    return;
                }

                container.innerHTML = '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-1"></div> Chargement...</div>';

                fetch('{{ route("admin.instances.whitelist.fetchUsers", $instance->id) }}')
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            container.innerHTML = '<div class="alert alert-warning mb-0">' + data.error + '</div>';
                            return;
                        }
                        cachedUsers = data;
                        renderUsers(data);
                    })
                    .catch(err => {
                        container.innerHTML = '<div class="alert alert-danger mb-0">Erreur de chargement</div>';
                    });
            }

            function renderUsers(users, filter = '') {
                const container = document.getElementById('azuriom-users-list');
                if (!container) return;

                const filtered = filter ? users.filter(u => u.name.toLowerCase().includes(filter.toLowerCase())) : users;

                if (filtered.length === 0) {
                    container.innerHTML = '<div class="text-muted text-center py-3">Aucun utilisateur trouvé</div>';
                    return;
                }

                container.innerHTML = filtered.map(u => `
                                <div class="form-check border-bottom py-2">
                                    <input class="form-check-input user-checkbox" type="checkbox" name="whitelist_users[]" value="${u.name}" id="user-${u.id}">
                                    <label class="form-check-label d-flex align-items-center gap-2" for="user-${u.id}">
                                        <strong>${u.name}</strong>
                                        <span class="badge" style="background-color: ${u.role_color}; color: #fff;">${u.role}</span>
                                        ${u.is_admin && u.role !== 'Admin' ? '<span class="badge bg-danger">Admin</span>' : ''}
                                    </label>
                                </div>
                            `).join('');

                updateUserButton();
            }

            function updateUserButton() {
                const btn = document.getElementById('btn-add-selected-users');
                if (!btn) return;
                const checked = document.querySelectorAll('.user-checkbox:checked').length;
                btn.disabled = checked === 0;
                btn.textContent = checked > 0 ? `Ajouter ${checked} utilisateur(s)` : 'Ajouter les sélectionnés';
            }

            // ======= ROLES =======
            function fetchRoles(forceRefresh = false) {
                const container = document.getElementById('azuriom-roles-list');
                if (!container) return;

                if (cachedRoles && !forceRefresh) {
                    renderRoles(cachedRoles);
                    return;
                }

                container.innerHTML = '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-1"></div> Chargement...</div>';

                fetch('{{ route("admin.instances.whitelist.fetchRoles", $instance->id) }}')
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            container.innerHTML = '<div class="alert alert-warning mb-0">' + data.error + '</div>';
                            return;
                        }
                        cachedRoles = data;
                        renderRoles(data);
                    })
                    .catch(err => {
                        container.innerHTML = '<div class="alert alert-danger mb-0">Erreur de chargement</div>';
                    });
            }

            function renderRoles(roles, filter = '') {
                const container = document.getElementById('azuriom-roles-list');
                if (!container) return;

                const filtered = filter ? roles.filter(r => r.name.toLowerCase().includes(filter.toLowerCase())) : roles;

                if (filtered.length === 0) {
                    container.innerHTML = '<div class="text-muted text-center py-3">Aucun rôle trouvé</div>';
                    return;
                }

                container.innerHTML = filtered.map(r => `
                                <div class="form-check border-bottom py-2">
                                    <input class="form-check-input role-checkbox" type="checkbox" name="azuriom_roles[]" value="${r.name}" id="role-${r.id}">
                                    <label class="form-check-label d-flex align-items-center gap-2" for="role-${r.id}">
                                        <span class="badge" style="background-color: ${r.color}; color: #fff;">${r.name}</span>
                                        <small class="text-muted">Power: ${r.power}</small>
                                        ${r.is_admin && r.name !== 'Admin' ? '<span class="badge bg-danger">Admin</span>' : ''}
                                    </label>
                                </div>
                            `).join('');

                updateRoleButton();
            }

            function updateRoleButton() {
                const btn = document.getElementById('btn-add-selected-roles');
                if (!btn) return;
                const checked = document.querySelectorAll('.role-checkbox:checked').length;
                btn.disabled = checked === 0;
                btn.textContent = checked > 0 ? `Ajouter ${checked} rôle(s)` : 'Ajouter les sélectionnés';
            }

            // ======= EVENT LISTENERS =======
            document.addEventListener('DOMContentLoaded', function () {
                fetchUsers();
                fetchRoles();

                const refreshUsers = document.getElementById('btn-refresh-users');
                if (refreshUsers) refreshUsers.addEventListener('click', () => fetchUsers(true));

                const refreshRoles = document.getElementById('btn-refresh-roles');
                if (refreshRoles) refreshRoles.addEventListener('click', () => fetchRoles(true));

                const searchUsers = document.getElementById('search-users');
                if (searchUsers) {
                    searchUsers.addEventListener('input', function () {
                        if (cachedUsers) renderUsers(cachedUsers, this.value);
                    });
                }

                const searchRoles = document.getElementById('search-roles');
                if (searchRoles) {
                    searchRoles.addEventListener('input', function () {
                        if (cachedRoles) renderRoles(cachedRoles, this.value);
                    });
                }

                document.addEventListener('change', function (e) {
                    if (e.target.classList.contains('user-checkbox')) updateUserButton();
                    if (e.target.classList.contains('role-checkbox')) updateRoleButton();
                });
            });
        </script>
    @endif
@endsection
