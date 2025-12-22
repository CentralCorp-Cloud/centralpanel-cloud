@extends('layouts.admin')

@section('title', 'Gestion de la Whitelist')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="container-fluid p-0">
        <h2 class="text-3xl fw-bold mb-4">Gestion de la Whitelist</h2>

        <!-- Activation de la whitelist -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route('admin.whitelist.store') }}" method="POST" id="whitelistForm">
                    @csrf
                    <fieldset class="border rounded p-3 mb-4">
                        <legend class="w-auto px-2">Activation</legend>
                        <div class="form-check form-switch">
                            <input type="hidden" name="whitelist" value="0">
                            <input type="checkbox" class="form-check-input" id="whitelist" name="whitelist" value="1"
                                   {{ ($securityOptions->whitelist ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="whitelist">Activer la whitelist</label>
                        </div>
                    </fieldset>

                    @if($hasAzuriomApi)
                        <!-- Section Utilisateurs Azuriom -->
                        <fieldset class="border rounded p-3 mb-4">
                            <legend class="w-auto px-2">Utilisateurs Azuriom</legend>
                            
                            <div class="d-flex gap-2 mb-3">
                                <button type="button" class="btn btn-outline-primary" id="refreshUsersBtn">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Rafraîchir la liste
                                </button>
                                <span class="text-muted align-self-center" id="usersCacheInfo"></span>
                            </div>

                            <div class="mb-3">
                                <input type="text" class="form-control" id="userSearchFilter" 
                                       placeholder="🔍 Filtrer les utilisateurs..." disabled>
                            </div>

                            <div id="usersListContainer" class="row" style="max-height: 400px; overflow-y: auto;">
                                <div class="col-12 text-center text-muted py-4">
                                    <i class="bi bi-arrow-clockwise fs-1"></i>
                                    <p>Cliquez sur "Rafraîchir la liste" pour charger les utilisateurs</p>
                                </div>
                            </div>
                        </fieldset>

                        <!-- Section Rôles Azuriom -->
                        <fieldset class="border rounded p-3 mb-4">
                            <legend class="w-auto px-2">Rôles Azuriom</legend>
                            
                            <div class="d-flex gap-2 mb-3">
                                <button type="button" class="btn btn-outline-primary" id="refreshRolesBtn">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Rafraîchir la liste
                                </button>
                                <span class="text-muted align-self-center" id="rolesCacheInfo"></span>
                            </div>

                            <div class="mb-3">
                                <input type="text" class="form-control" id="roleSearchFilter" 
                                       placeholder="🔍 Filtrer les rôles..." disabled>
                            </div>

                            <div id="rolesListContainer" class="row" style="max-height: 300px; overflow-y: auto;">
                                <div class="col-12 text-center text-muted py-4">
                                    <i class="bi bi-arrow-clockwise fs-1"></i>
                                    <p>Cliquez sur "Rafraîchir la liste" pour charger les rôles</p>
                                </div>
                            </div>
                        </fieldset>
                    @else
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            L'API Azuriom n'est pas configurée. Configurez-la dans les paramètres généraux pour ajouter des utilisateurs et rôles.
                        </div>
                    @endif

                    <!-- Bouton de soumission -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">💾 Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des utilisateurs dans la Whitelist -->
        <h3 class="mb-3">Utilisateurs dans la Whitelist</h3>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                @if($users->isEmpty())
                    <p class="text-muted">Aucun utilisateur actuellement whitelisté.</p>
                @else
                    <ul class="list-group" id="whitelistedUsersList">
                        @foreach($users as $user)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-truncate whitelist-username" style="max-width: 200px;">{{ $user->users }}</span>
                                <form action="{{ route('admin.whitelist.destroyUser', $user->id) }}" method="POST" class="ms-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <!-- Liste des rôles dans la Whitelist -->
        <h3 class="mb-3">Rôles dans la Whitelist</h3>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                @if($roles->isEmpty())
                    <p class="text-muted">Aucun rôle actuellement whitelisté.</p>
                @else
                    <ul class="list-group" id="whitelistedRolesList">
                        @foreach($roles as $role)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-truncate whitelist-rolename" style="max-width: 200px;">{{ $role->role }}</span>
                                <form action="{{ route('admin.whitelist.destroyRole', $role->id) }}" method="POST" class="ms-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const CACHE_KEY_USERS = 'whitelist_users_cache';
    const CACHE_KEY_ROLES = 'whitelist_roles_cache';

    // Extraire les utilisateurs et rôles déjà whitelistés depuis la page HTML
    const whitelistedUsers = new Set();
    const whitelistedRoles = new Set();
    
    document.querySelectorAll('#whitelistedUsersList .whitelist-username').forEach(el => {
        whitelistedUsers.add(el.textContent.trim());
    });
    document.querySelectorAll('#whitelistedRolesList .whitelist-rolename').forEach(el => {
        whitelistedRoles.add(el.textContent.trim());
    });

    // Fonctions de filtrage
    function filterWhitelistedUsers(users) {
        return users.filter(u => !whitelistedUsers.has(u.name));
    }
    function filterWhitelistedRoles(roles) {
        return roles.filter(r => !whitelistedRoles.has(r.name));
    }

    // ===== USERS =====
    const refreshUsersBtn = document.getElementById('refreshUsersBtn');
    const userSearchFilter = document.getElementById('userSearchFilter');
    const usersListContainer = document.getElementById('usersListContainer');
    const usersCacheInfo = document.getElementById('usersCacheInfo');
    
    let usersCache = loadCache(CACHE_KEY_USERS);

    if (refreshUsersBtn) {
        // Afficher le cache existant au chargement (filtré)
        if (usersCache && usersCache.data) {
            renderUsers(filterWhitelistedUsers(usersCache.data));
            updateCacheInfo(usersCacheInfo, usersCache.timestamp);
            userSearchFilter.disabled = false;
        }

        refreshUsersBtn.addEventListener('click', function() {
            refreshUsersBtn.disabled = true;
            refreshUsersBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Chargement...';
            
            fetch('{{ route('admin.whitelist.fetchUsers') }}')
                .then(res => res.json())
                .then(users => {
                    usersCache = { data: users, timestamp: Date.now() };
                    saveCache(CACHE_KEY_USERS, usersCache);
                    renderUsers(filterWhitelistedUsers(users));
                    updateCacheInfo(usersCacheInfo, usersCache.timestamp);
                    userSearchFilter.disabled = false;
                })
                .catch(err => {
                    console.error('Erreur:', err);
                    usersListContainer.innerHTML = '<div class="col-12 text-danger">Erreur de chargement</div>';
                })
                .finally(() => {
                    refreshUsersBtn.disabled = false;
                    refreshUsersBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Rafraîchir la liste';
                });
        });

        userSearchFilter.addEventListener('input', function() {
            if (usersCache && usersCache.data) {
                const query = this.value.toLowerCase();
                const filtered = filterWhitelistedUsers(usersCache.data).filter(u => u.name.toLowerCase().includes(query));
                renderUsers(filtered);
            }
        });
    }

    function renderUsers(users) {
        if (users.length === 0) {
            usersListContainer.innerHTML = '<div class="col-12 text-muted">Aucun utilisateur disponible</div>';
            return;
        }
        usersListContainer.innerHTML = users.map(user => `
            <div class="col-md-4 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="whitelist_users[]"
                           value="${escapeHtml(user.name)}" id="user_${user.id}">
                    <label class="form-check-label" for="user_${user.id}">
                        <strong class="text-truncate" style="max-width: 180px;">${escapeHtml(user.name)}</strong>
                        ${user.is_admin ? '<span class="badge bg-danger ms-1">Admin</span>' : 
                            `<span class="badge ms-1" style="color: ${user.role_color};">(${escapeHtml(user.role)})</span>`}
                    </label>
                </div>
            </div>
        `).join('');
    }

    // ===== ROLES =====
    const refreshRolesBtn = document.getElementById('refreshRolesBtn');
    const roleSearchFilter = document.getElementById('roleSearchFilter');
    const rolesListContainer = document.getElementById('rolesListContainer');
    const rolesCacheInfo = document.getElementById('rolesCacheInfo');
    
    let rolesCache = loadCache(CACHE_KEY_ROLES);

    if (refreshRolesBtn) {
        // Afficher le cache existant au chargement (filtré)
        if (rolesCache && rolesCache.data) {
            renderRoles(filterWhitelistedRoles(rolesCache.data));
            updateCacheInfo(rolesCacheInfo, rolesCache.timestamp);
            roleSearchFilter.disabled = false;
        }

        refreshRolesBtn.addEventListener('click', function() {
            refreshRolesBtn.disabled = true;
            refreshRolesBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Chargement...';
            
            fetch('{{ route('admin.whitelist.fetchRoles') }}')
                .then(res => res.json())
                .then(roles => {
                    rolesCache = { data: roles, timestamp: Date.now() };
                    saveCache(CACHE_KEY_ROLES, rolesCache);
                    renderRoles(filterWhitelistedRoles(roles));
                    updateCacheInfo(rolesCacheInfo, rolesCache.timestamp);
                    roleSearchFilter.disabled = false;
                })
                .catch(err => {
                    console.error('Erreur:', err);
                    rolesListContainer.innerHTML = '<div class="col-12 text-danger">Erreur de chargement</div>';
                })
                .finally(() => {
                    refreshRolesBtn.disabled = false;
                    refreshRolesBtn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i> Rafraîchir la liste';
                });
        });

        roleSearchFilter.addEventListener('input', function() {
            if (rolesCache && rolesCache.data) {
                const query = this.value.toLowerCase();
                const filtered = filterWhitelistedRoles(rolesCache.data).filter(r => r.name.toLowerCase().includes(query));
                renderRoles(filtered);
            }
        });
    }

    function renderRoles(roles) {
        if (roles.length === 0) {
            rolesListContainer.innerHTML = '<div class="col-12 text-muted">Aucun rôle disponible</div>';
            return;
        }
        rolesListContainer.innerHTML = roles.map(role => `
            <div class="col-md-4 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="azuriom_roles[]"
                           value="${escapeHtml(role.name)}" id="role_${role.id}">
                    <label class="form-check-label" for="role_${role.id}">
                        <strong style="color: ${role.color};">${escapeHtml(role.name)}</strong>
                        ${role.is_admin ? '<span class="badge bg-danger ms-1">Admin</span>' : ''}
                        <span class="badge bg-secondary ms-1">Power: ${role.power}</span>
                    </label>
                </div>
            </div>
        `).join('');
    }

    // ===== UTILITIES =====
    function loadCache(key) {
        try {
            const cached = localStorage.getItem(key);
            return cached ? JSON.parse(cached) : null;
        } catch (e) {
            return null;
        }
    }

    function saveCache(key, data) {
        try {
            localStorage.setItem(key, JSON.stringify(data));
        } catch (e) {
            console.warn('Impossible de sauvegarder le cache:', e);
        }
    }

    function updateCacheInfo(element, timestamp) {
        if (!element) return;
        const date = new Date(timestamp);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000 / 60);
        
        if (diff < 1) {
            element.textContent = '(mis à jour à l\'instant)';
        } else if (diff < 60) {
            element.textContent = `(mis à jour il y a ${diff} min)`;
        } else {
            element.textContent = `(mis à jour le ${date.toLocaleDateString()} à ${date.toLocaleTimeString()})`;
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endsection
