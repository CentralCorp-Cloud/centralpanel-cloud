@extends('layouts.admin')

@section('title', $instance ? __('messages.instances.form.edit_title', ['name' => $instance->display_name]) : __('messages.instances.form.create_title'))
@section('page-title', $instance ? __('messages.instances.form.edit_title', ['name' => $instance->display_name]) : __('messages.instances.form.create_title'))

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
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.instances.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> {{ __('messages.instances.back') }}
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form id="instance-form"
                action="{{ $instance ? route('admin.instances.update', $instance->id) : route('admin.instances.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @if ($instance)
                    @method('PUT')
                @endif

                {{-- Identité --}}
                <fieldset class="border p-3 mb-4 rounded">
                    <legend class="float-none w-auto px-2">{{ __('messages.instances.form.identity') }}</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="display_name" class="form-label">{{ __('messages.instances.form.display_name') }}</label>
                            <input type="text" class="form-control" id="display_name" name="display_name"
                                value="{{ old('display_name', $instance->display_name ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label">{{ __('messages.instances.form.slug') }}</label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="{{ old('name', $instance->name ?? '') }}" required
                                title="{{ __('messages.instances.form.slug_rule') }}">
                            <div class="form-text">{{ __('messages.instances.form.slug_help') }}</div>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">{{ __('messages.instances.form.description') }}</label>
                            <textarea class="form-control" id="description" name="description" rows="3" maxlength="1000"
                                placeholder="{{ __('messages.instances.form.description_placeholder') }}">{{ old('description', $instance->description ?? '') }}</textarea>
                            <div class="form-text">{{ __('messages.instances.form.description_help') }}</div>
                        </div>
                    </div>
                </fieldset>

                {{-- Serveur --}}
                <fieldset class="border p-3 mb-4 rounded">
                    <legend class="float-none w-auto px-2">{{ __('messages.instances.form.server') }}</legend>

                    @if (($authMode ?? 'azuriom') === 'azuriom')
                        {{-- Azuriom mode: select from API servers --}}
                        <div class="mb-3">
                            <label for="azuriom_server_id" class="form-label">{{ __('messages.instances.form.azuriom_server') }}</label>
                            <div class="d-flex gap-2">
                                <select class="form-select" id="azuriom_server_id" name="azuriom_server_id">
                                    <option value="">{{ __('messages.instances.form.loading_option') }}</option>
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-refresh-servers">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">{{ __('messages.instances.form.azuriom_server_help') }}</small>
                        </div>
                        <input type="hidden" id="server_ip" name="server_ip"
                            value="{{ old('server_ip', $instance->server_ip ?? '') }}">
                        <input type="hidden" id="server_port" name="server_port"
                            value="{{ old('server_port', $instance->server_port ?? '') }}">
                        <input type="hidden" id="server_name" name="server_name"
                            value="{{ old('server_name', $instance->server_name ?? '') }}">
                    @else
                        {{-- Microsoft mode: manual server inputs --}}
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="server_name" class="form-label">{{ __('messages.instances.form.server_name') }}</label>
                                <input type="text" class="form-control" id="server_name" name="server_name"
                                    value="{{ old('server_name', $instance->server_name ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="server_ip" class="form-label">{{ __('messages.instances.form.server_ip') }}</label>
                                <input type="text" class="form-control" id="server_ip" name="server_ip"
                                    value="{{ old('server_ip', $instance->server_ip ?? '') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="server_port" class="form-label">{{ __('messages.instances.form.server_port') }}</label>
                                <input type="text" class="form-control" id="server_port" name="server_port" placeholder="25565"
                                    value="{{ old('server_port', $instance->server_port ?? '') }}">
                            </div>
                        </div>
                    @endif

                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="server_icon_file" class="form-label">{{ __('messages.instances.form.server_icon') }}</label>
                            <input type="file" class="form-control" id="server_icon_file" name="server_icon_file"
                                accept="image/*">
                            @if ($instance && $instance->server_icon)
                                <div class="mt-2 d-flex align-items-center gap-2">
                                    <img src="{{ asset('storage/' . $instance->server_icon) }}" alt="{{ __('messages.instances.form.server_icon_alt') }}"
                                        style="height: 32px;">
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                        onclick="deleteIcon()">{{ __('messages.instances.form.delete_icon') }}</button>
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label for="server_icon_url" class="form-label">{{ __('messages.instances.form.remote_icon_url') }}</label>
                            <input type="url" class="form-control" id="server_icon_url" name="server_icon_url"
                                value="{{ old('server_icon_url', $instance->server_icon_url ?? '') }}">
                        </div>
                    </div>
                </fieldset>

                {{-- Loader --}}
                <fieldset class="border p-3 mb-4 rounded">
                    <legend class="float-none w-auto px-2">{{ __('messages.instances.form.loader') }}</legend>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="minecraft_version" class="form-label">{{ __('messages.instances.form.minecraft_version') }}</label>
                            <input type="text" class="form-control" id="minecraft_version" name="minecraft_version"
                                value="{{ old('minecraft_version', $instance->minecraft_version ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label for="loader_type" class="form-label">{{ __('messages.instances.form.loader_type') }}</label>
                            <select class="form-select" id="loader_type" name="loader_type">
                                <option value="">{{ __('messages.common.none') }}</option>
                                <option value="forge" {{ old('loader_type', $instance->loader_type ?? '') === 'forge' ? 'selected' : '' }}>Forge</option>
                                <option value="neoforge" {{ old('loader_type', $instance->loader_type ?? '') === 'neoforge' ? 'selected' : '' }}>NeoForge</option>
                                <option value="fabric" {{ old('loader_type', $instance->loader_type ?? '') === 'fabric' ? 'selected' : '' }}>Fabric</option>
                                <option value="legacyfabric" {{ old('loader_type', $instance->loader_type ?? '') === 'legacyfabric' ? 'selected' : '' }}>Legacy Fabric</option>
                                <option value="quilt" {{ old('loader_type', $instance->loader_type ?? '') === 'quilt' ? 'selected' : '' }}>Quilt</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="loader_build_version" class="form-label">{{ __('messages.instances.form.loader_build') }}</label>
                            {{-- Dynamic: select for forge/fabric, text input for others --}}
                            <select class="form-select" id="loader_build_select" style="display:none;"></select>
                            <input type="text" class="form-control" id="loader_build_manual" style="display:none;"
                                placeholder="Ex: 0.16.14 ou 47.3.12">
                            <input type="hidden" id="loader_build_version" name="loader_build_version"
                                value="{{ old('loader_build_version', $instance->loader_build_version ?? '') }}">
                        </div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input type="hidden" name="loader_activation" value="0">
                        <input type="checkbox" class="form-check-input" id="loader_activation" name="loader_activation"
                            value="1" {{ old('loader_activation', $instance->loader_activation ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="loader_activation">{{ __('messages.instances.form.enable_loader') }}</label>
                    </div>
                </fieldset>

                {{-- Apparence --}}
                <fieldset class="border p-3 mb-4 rounded">
                    <legend class="float-none w-auto px-2">{{ __('messages.instances.form.appearance') }}</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="background_file" class="form-label">{{ __('messages.instances.form.default_background') }}</label>
                            <input type="file" class="form-control" id="background_file" name="background_file"
                                accept="image/*">
                            @if ($instance && $instance->background_default)
                                <div class="mt-2">
                                    <img src="{{ asset('storage/' . $instance->background_default) }}" alt="{{ __('messages.instances.form.background_alt') }}"
                                        style="height: 80px; border-radius: 8px;">
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <label for="rpc_details_override" class="form-label">{{ __('messages.instances.form.rpc_details') }}</label>
                            <input type="text" class="form-control" id="rpc_details_override" name="rpc_details_override"
                                value="{{ old('rpc_details_override', $instance->rpc_details_override ?? '') }}"
                                placeholder="{{ __('messages.instances.form.rpc_placeholder') }}">
                        </div>
                    </div>
                </fieldset>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        💾 {{ $instance ? __('messages.instances.form.update') : __('messages.instances.form.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const instanceI18n = {{ Illuminate\Support\Js::from([
            'slugRule' => __('messages.instances.form.slug_rule'),
            'loading' => __('messages.instances.form.loading_option'),
            'error' => __('messages.instances.form.load_error'),
            'errorOption' => __('messages.instances.form.error_option'),
            'none' => __('messages.instances.form.none_option'),
            'loadError' => __('messages.instances.form.load_error_option'),
            'deleteIconConfirm' => __('messages.instances.form.delete_icon_confirm'),
        ]) }};

        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('instance-form');
            const loaderType = document.getElementById('loader_type');
            const mcVersion = document.getElementById('minecraft_version');
            const buildSelect = document.getElementById('loader_build_select');
            const buildManual = document.getElementById('loader_build_manual');
            const buildHidden = document.getElementById('loader_build_version');
            const currentVersion = buildHidden.value;
            const nameInput = document.getElementById('name');

            // ======= NAME VALIDATION (replaces broken html pattern) =======
            if (nameInput) {
                nameInput.addEventListener('input', function () {
                    const valid = /^[a-zA-Z0-9_-]+$/.test(this.value) || this.value === '';
                    this.setCustomValidity(valid ? '' : instanceI18n.slugRule);
                });
            }

            // ======= LOADER VERSION LOGIC =======
            // Loaders with API-fetched version lists
            const apiLoaders = ['forge', 'fabric'];
            // Loaders with manual version input
            const manualLoaders = ['neoforge', 'legacyfabric', 'quilt'];

            function syncBuildVersion() {
                const type = loaderType.value;
                if (apiLoaders.includes(type)) {
                    buildHidden.value = buildSelect.value;
                } else if (manualLoaders.includes(type)) {
                    buildHidden.value = buildManual.value;
                }
            }

            function showSelect() {
                buildSelect.style.display = '';
                buildManual.style.display = 'none';
            }

            function showManual() {
                buildSelect.style.display = 'none';
                buildManual.style.display = '';
                buildManual.value = currentVersion;
            }

            function showNone() {
                buildSelect.style.display = 'none';
                buildManual.style.display = 'none';
            }

            function populateSelect(options, currentVal) {
                buildSelect.innerHTML = '';
                options.forEach(val => {
                    const opt = document.createElement('option');
                    opt.value = val;
                    opt.text = val;
                    if (val === currentVal) opt.selected = true;
                    buildSelect.appendChild(opt);
                });
                showSelect();
                syncBuildVersion();
            }

            function loadForgeBuilds(version) {
                if (!version) return;
                buildSelect.innerHTML = `<option>${instanceI18n.loading}</option>`;
                showSelect();
                fetch(`/admin/instances/loader/builds?mc_version=${version}`)
                    .then(r => r.json())
                    .then(data => populateSelect(data.builds || [], currentVersion))
                    .catch(() => { buildSelect.innerHTML = `<option>${instanceI18n.error}</option>`; });
            }

            function loadFabricVersions() {
                buildSelect.innerHTML = `<option>${instanceI18n.loading}</option>`;
                showSelect();
                fetch('/admin/instances/loader/fabric-versions')
                    .then(r => r.json())
                    .then(data => {
                        const versions = (data.versions || []).map(v => v.version || v);
                        populateSelect(versions, currentVersion);
                    })
                    .catch(() => { buildSelect.innerHTML = `<option>${instanceI18n.error}</option>`; });
            }

            function updateBuilds() {
                const type = loaderType.value;
                if (type === 'forge') {
                    loadForgeBuilds(mcVersion.value);
                } else if (type === 'fabric') {
                    loadFabricVersions();
                } else if (manualLoaders.includes(type)) {
                    showManual();
                } else {
                    showNone();
                    buildHidden.value = '';
                }
            }

            loaderType.addEventListener('change', updateBuilds);
            mcVersion.addEventListener('change', updateBuilds);
            mcVersion.addEventListener('blur', updateBuilds);
            buildSelect.addEventListener('change', syncBuildVersion);
            buildManual.addEventListener('input', syncBuildVersion);

            // Sync on form submit
            if (form) {
                form.addEventListener('submit', syncBuildVersion);
            }

            // Initial load
            updateBuilds();

            @if (($authMode ?? 'azuriom') === 'azuriom')
                // ======= AZURIOM SERVER FETCH =======
                const serverSelect = document.getElementById('azuriom_server_id');
                const btnRefreshServers = document.getElementById('btn-refresh-servers');
                const currentServerIp = '{{ old('server_ip', $instance->server_ip ?? '') }}';
                const currentServerPort = '{{ old('server_port', $instance->server_port ?? '') }}';

                function fetchServers() {
                    serverSelect.innerHTML = `<option value="">${instanceI18n.loading}</option>`;
                    fetch('{{ route("admin.instances.fetchServers") }}')
                        .then(r => r.json())
                        .then(data => {
                            if (data.error) {
                                serverSelect.innerHTML = `<option value="">${instanceI18n.errorOption.replace(':message', data.error)}</option>`;
                                return;
                            }
                            serverSelect.innerHTML = `<option value="">${instanceI18n.none}</option>`;
                            (data || []).forEach(srv => {
                                const opt = document.createElement('option');
                                opt.value = srv.id;
                                opt.textContent = srv.name + ' (' + srv.ip + ':' + srv.port + ')';
                                opt.dataset.ip = srv.ip;
                                opt.dataset.port = srv.port;
                                opt.dataset.name = srv.name;
                                opt.dataset.icon = srv.icon || '';
                                // Auto-select matching server
                                if (srv.ip === currentServerIp && srv.port === currentServerPort) {
                                    opt.selected = true;
                                }
                                serverSelect.appendChild(opt);
                            });
                        })
                        .catch(() => {
                            serverSelect.innerHTML = `<option value="">${instanceI18n.loadError}</option>`;
                        });
                }

                serverSelect.addEventListener('change', function () {
                    const selected = this.options[this.selectedIndex];
                    if (selected && selected.value) {
                        document.getElementById('server_ip').value = selected.dataset.ip || '';
                        document.getElementById('server_port').value = selected.dataset.port || '';
                        document.getElementById('server_name').value = selected.dataset.name || '';
                        document.getElementById('server_icon_url').value = selected.dataset.icon || '';
                    }
                });

                if (btnRefreshServers) btnRefreshServers.addEventListener('click', fetchServers);

                // Initial load
                fetchServers();
            @endif
                    });

        @if ($instance && $instance->server_icon)
            function deleteIcon() {
                if (!confirm(instanceI18n.deleteIconConfirm)) return;
                fetch('{{ route("admin.instances.deleteIcon", $instance->id) }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                }).then(r => {
                    if (r.ok || r.redirected) window.location.reload();
                });
            }
        @endif
    </script>
@endsection
