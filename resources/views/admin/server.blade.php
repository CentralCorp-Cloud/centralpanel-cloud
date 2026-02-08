@extends('layouts.admin')

@php
    use App\Models\OptionsServer;
@endphp

@section('title', __('messages.server.title'))

@section('content')
    <div class="container-fluid p-0">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold mb-0">{{ __('messages.server.list_title') }}</h2>
            <form method="POST" action="{{ route('admin.server.sync') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-repeat me-1"></i> {{ __('messages.server.sync_btn') }}
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('messages.common.close') }}"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('messages.common.close') }}"></button>
            </div>
        @endif

        @if($error)
            <div class="alert alert-danger d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-2"></i> {{ $error }}
            </div>
        @elseif(!$options)
            <div class="alert alert-warning d-flex align-items-center">
                <i class="fas fa-cogs me-2"></i> {{ __('messages.server.config_error') }}
            </div>
        @else
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="card-title mb-0">{{ __('messages.server.synced_servers') }}</h5>
                </div>
                <div class="card-body">
                    @if(empty($servers))
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="fas fa-info-circle me-2"></i> {{ __('messages.server.no_servers') }}
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('messages.server.name') }}</th>
                                        <th>{{ __('messages.server.address') }}</th>
                                        <th>{{ __('messages.server.port') }}</th>
                                        <th>{{ __('messages.server.type') }}</th>
                                        <th>{{ __('messages.server.icon') }}</th>
                                        <th class="text-center" style="width: 150px;">{{ __('messages.common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($servers as $server)
                                        <tr>
                                            <td><strong>{{ $server['name'] }}</strong></td>
                                            <td><code>{{ $server['address'] }}</code></td>
                                            <td>{{ $server['port'] }}</td>
                                            <td><span class="badge bg-info">{{ $server['type'] }}</span></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    @if($server['icon_url'])
                                                        <img src="{{ $server['icon_url'] }}"
                                                             alt="{{ __('messages.server.icon') }}"
                                                             class="img-thumbnail rounded-circle"
                                                             style="max-width: 40px; max-height: 40px;">
                                                        @if($server['icon_local'])
                                                            <span class="badge bg-success" title="{{ __('messages.server.icon_local') }}">
                                                                <i class="bi bi-hdd"></i>
                                                            </span>
                                                        @endif
                                                    @else
                                                        <span class="text-muted">{{ __('messages.common.none') }}</span>
                                                    @endif

                                                    {{-- Upload icon form --}}
                                                    <form action="{{ route('admin.server.updateIcon', $server['id']) }}" method="POST" enctype="multipart/form-data" class="d-inline">
                                                        @csrf
                                                        <label class="btn btn-sm btn-outline-secondary" title="{{ __('messages.server.upload_icon') }}">
                                                            <i class="bi bi-upload"></i>
                                                            <input type="file" name="icon" class="d-none" accept="image/*" onchange="this.form.submit()">
                                                        </label>
                                                    </form>

                                                    {{-- Delete local icon form --}}
                                                    @if($server['icon_local'])
                                                        <form action="{{ route('admin.server.deleteIcon', $server['id']) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('messages.server.delete_icon') }}" onclick="return confirm('{{ __('messages.server.confirm_delete_icon') }}')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if(!($defaultServers[$server['id']] ?? false))
                                                    <form method="POST" action="{{ route('admin.server.set-default') }}" style="display: inline;" class="set-default-form">
                                                        @csrf
                                                        <input type="hidden" name="server_id" value="{{ $server['id'] }}">
                                                        <button type="submit" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-star"></i> {{ __('messages.server.set_default') }}
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-success fw-bold">
                                                        <i class="bi bi-check-circle-fill"></i> {{ __('messages.server.is_default') }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>{{ __('messages.server.default_info') }}</strong>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if($options && !empty($servers))
        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const forms = document.querySelectorAll('.set-default-form');

                forms.forEach(form => {
                    form.addEventListener('submit', function(e) {
                        const serverName = this.closest('tr').querySelector('td:nth-child(2)').textContent.trim();

                        if (!confirm(`{{ __('messages.server.confirm_default') }}`)) {
                            e.preventDefault();
                            return false;
                        }

                        const button = this.querySelector('button');
                        button.disabled = true;
                        button.innerHTML = '<i class="bi bi-hourglass-split"></i> {{ __('messages.server.processing') }}';
                    });
                });
            });
        </script>
        @endpush
    @endif
@endsection
