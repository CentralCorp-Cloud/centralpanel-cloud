@extends('layouts.admin')

@section('title', __('messages.instances.backgrounds.title', ['name' => $instance->display_name]))
@section('page-title', __('messages.instances.backgrounds.header', ['name' => $instance->display_name]))

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.instances.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> {{ __('messages.instances.back') }}
        </a>
    </div>

    <div class="alert alert-info">
        {{ __('messages.instances.backgrounds.intro') }}
    </div>

    <div class="row g-4">
        {{-- Left: Add roles --}}
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('messages.instances.backgrounds.add_roles') }}</h5>
                    @if (($hasAzuriomApi ?? false))
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-refresh-bg-roles">
                            <i class="bi bi-arrow-clockwise me-1"></i> {{ __('messages.instances.backgrounds.refresh') }}
                        </button>
                    @endif
                </div>
                <div class="card-body">
                    @if (($hasAzuriomApi ?? false))
                        {{-- Azuriom mode: API role selection --}}
                        <div class="mb-3">
                            <input type="text" class="form-control" id="search-bg-roles" placeholder="{{ __('messages.instances.backgrounds.search_role') }}">
                        </div>
                        <form action="{{ route('admin.instances.bg.update', $instance->id) }}" method="POST"
                            id="form-add-bg-roles">
                            @csrf
                            <div id="azuriom-bg-roles-list" style="max-height: 300px; overflow-y: auto;" class="mb-3">
                                <div class="text-center text-muted py-3">
                                    <div class="spinner-border spinner-border-sm me-1"></div> {{ __('messages.instances.backgrounds.loading') }}
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="btn-add-bg-roles" disabled>
                                <i class="bi bi-plus-circle me-1"></i> {{ __('messages.instances.backgrounds.add_selected') }}
                            </button>
                        </form>
                    @else
                        {{-- Fallback: manual input --}}
                        <form action="{{ route('admin.instances.bg.update', $instance->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label for="role_name" class="form-label">{{ __('messages.instances.backgrounds.role_name') }}</label>
                                <input type="text" class="form-control" id="role_name" name="role_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="role_background" class="form-label">{{ __('messages.instances.backgrounds.optional_image') }}</label>
                                <input type="file" class="form-control" id="role_background" name="role_background"
                                    accept="image/*">
                            </div>
                            <div class="text-center text-muted small mb-3">— {{ __('messages.instances.backgrounds.or') }} —</div>
                            <div class="mb-3">
                                <label for="role_video_url" class="form-label">{{ __('messages.instances.backgrounds.youtube_url') }}</label>
                                <input type="url" class="form-control" id="role_video_url" name="role_video_url"
                                    placeholder="https://www.youtube.com/watch?v=...">
                                <div class="form-text">{{ __('messages.instances.backgrounds.video_help') }}</div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus me-1"></i> {{ __('messages.instances.backgrounds.add') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right: Current backgrounds --}}
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('messages.instances.backgrounds.current', ['count' => $roles->count()]) }}</h5>
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                        @forelse ($roles as $bg)
                            @php($videoId = \App\Support\YouTube::videoId($bg->video_url))
                            <div class="col">
                                <div class="card h-100">
                                    @if ($videoId)
                                        <div class="card-img-top position-relative overflow-hidden bg-dark" style="height: 120px;">
                                            <img src="https://i.ytimg.com/vi/{{ $videoId }}/hqdefault.jpg"
                                                alt="{{ $bg->role_name }}" class="w-100 h-100" style="object-fit: cover; opacity: .72;">
                                            <div class="position-absolute top-50 start-50 translate-middle text-white text-center">
                                                <i class="bi bi-play-circle-fill fs-1"></i>
                                                <div class="small fw-semibold">{{ __('messages.instances.backgrounds.video_background') }}</div>
                                            </div>
                                        </div>
                                    @elseif ($bg->image_path)
                                        <img src="{{ asset('storage/' . $bg->image_path) }}" class="card-img-top"
                                            alt="{{ $bg->role_name }}" style="height: 120px; object-fit: cover;">
                                    @else
                                        <div class="card-img-top bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center"
                                            style="height: 120px;">
                                            <span class="text-muted"><i class="bi bi-image me-1"></i> {{ __('messages.instances.backgrounds.no_media') }}</span>
                                        </div>
                                    @endif
                                    <div class="card-body py-2">
                                        <strong>{{ $bg->role_name }}</strong>
                                        @if ($bg->video_url)
                                            <div class="small text-muted text-truncate mt-1" title="{{ $bg->video_url }}">
                                                <i class="bi bi-youtube me-1"></i>{{ $bg->video_url }}
                                            </div>
                                        @endif
                                        {{-- Replace the current media with an image or a video --}}
                                        <form action="{{ route('admin.instances.bg.update', $instance->id) }}" method="POST"
                                            enctype="multipart/form-data" class="mt-2">
                                            @csrf
                                            <input type="hidden" name="role_name" value="{{ $bg->role_name }}">
                                            <div class="mb-2">
                                                <label class="form-label small mb-1">{{ __('messages.instances.backgrounds.new_image') }}</label>
                                                <input type="file" class="form-control form-control-sm" name="role_background"
                                                    accept="image/*">
                                            </div>
                                            <div class="text-center text-muted small mb-2">{{ __('messages.instances.backgrounds.or') }}</div>
                                            <div class="mb-2">
                                                <label class="form-label small mb-1">{{ __('messages.instances.backgrounds.new_youtube_url') }}</label>
                                                <input type="url" class="form-control form-control-sm" name="role_video_url"
                                                    placeholder="https://youtu.be/...">
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                                <i class="bi bi-arrow-repeat me-1"></i> {{ __('messages.instances.backgrounds.replace_media') }}
                                            </button>
                                        </form>
                                    </div>
                                    <div class="card-footer bg-transparent text-end py-1">
                                        <form action="{{ route('admin.instances.bg.destroy', [$instance->id, $bg->id]) }}"
                                            method="POST" class="d-inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i> {{ __('messages.instances.backgrounds.delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col">
                                <p class="text-muted">{{ __('messages.instances.backgrounds.none_configured') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (($hasAzuriomApi ?? false))
        <script>
            const backgroundsI18n = {{ Illuminate\Support\Js::from([
                'loading' => __('messages.instances.backgrounds.loading'),
                'loadError' => __('messages.instances.backgrounds.load_error'),
                'noRoleAvailable' => __('messages.instances.backgrounds.no_role_available'),
                'addSelected' => __('messages.instances.backgrounds.add_selected'),
                'selectedRole' => trans_choice('messages.instances.backgrounds.selected_roles', 1, ['count' => ':count']),
                'selectedRoles' => trans_choice('messages.instances.backgrounds.selected_roles', 2, ['count' => ':count']),
                'power' => __('messages.instances.backgrounds.power', ['power' => ':power']),
            ]) }};
            let cachedBgRoles = null;

            function fetchBgRoles(forceRefresh = false) {
                const container = document.getElementById('azuriom-bg-roles-list');
                if (!container) return;

                if (cachedBgRoles && !forceRefresh) {
                    renderBgRoles(cachedBgRoles);
                    return;
                }

                container.innerHTML = `<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm me-1"></div> ${backgroundsI18n.loading}</div>`;

                fetch('{{ route("admin.instances.bg.fetchRoles", $instance->id) }}')
                    .then(r => r.json())
                    .then(data => {
                        if (data.error) {
                            container.innerHTML = '<div class="alert alert-warning mb-0">' + data.error + '</div>';
                            return;
                        }
                        cachedBgRoles = data;
                        renderBgRoles(data);
                    })
                    .catch(err => {
                        container.innerHTML = `<div class="alert alert-danger mb-0">${backgroundsI18n.loadError}</div>`;
                    });
            }

            function renderBgRoles(roles, filter = '') {
                const container = document.getElementById('azuriom-bg-roles-list');
                if (!container) return;

                const filtered = filter ? roles.filter(r => r.name.toLowerCase().includes(filter.toLowerCase())) : roles;

                if (filtered.length === 0) {
                    container.innerHTML = `<div class="text-muted text-center py-3">${backgroundsI18n.noRoleAvailable}</div>`;
                    return;
                }

                container.innerHTML = filtered.map(r => `
                                        <div class="form-check border-bottom py-2">
                                            <input class="form-check-input bg-role-checkbox" type="checkbox" value="${r.name}" id="bg-role-${r.id}" data-role-id="${r.id}">
                                            <input type="hidden" name="azuriom_bg_roles[]" value="${r.name}" disabled class="bg-role-hidden" data-for="${r.id}">
                                            <input type="hidden" name="azuriom_bg_role_ids[]" value="${r.id}" disabled class="bg-role-id-hidden" data-for="${r.id}">
                                            <label class="form-check-label d-flex align-items-center gap-2" for="bg-role-${r.id}">
                                                <span class="badge" style="background-color: ${r.color}; color: #fff;">${r.name}</span>
                                                <small class="text-muted">${backgroundsI18n.power.replace(':power', r.power)}</small>
                                                ${r.is_admin && r.name !== 'Admin' ? '<span class="badge bg-danger">Admin</span>' : ''}
                                            </label>
                                        </div>
                                    `).join('');

                updateBgRoleButton();
            }

            function updateBgRoleButton() {
                const btn = document.getElementById('btn-add-bg-roles');
                if (!btn) return;
                const checked = document.querySelectorAll('.bg-role-checkbox:checked').length;
                btn.disabled = checked === 0;
                btn.textContent = checked === 0
                    ? backgroundsI18n.addSelected
                    : (checked === 1 ? backgroundsI18n.selectedRole : backgroundsI18n.selectedRoles).replace(':count', checked);
            }

            document.addEventListener('DOMContentLoaded', function () {
                fetchBgRoles();

                const refresh = document.getElementById('btn-refresh-bg-roles');
                if (refresh) refresh.addEventListener('click', () => fetchBgRoles(true));

                const search = document.getElementById('search-bg-roles');
                if (search) {
                    search.addEventListener('input', function () {
                        if (cachedBgRoles) renderBgRoles(cachedBgRoles, this.value);
                    });
                }

                document.addEventListener('change', function (e) {
                    if (e.target.classList.contains('bg-role-checkbox')) {
                        const roleId = e.target.dataset.roleId;
                        const hiddens = document.querySelectorAll(`[data-for="${roleId}"]`);
                        hiddens.forEach(h => h.disabled = !e.target.checked);
                        updateBgRoleButton();
                    }
                });
            });
        </script>
    @endif
@endsection
