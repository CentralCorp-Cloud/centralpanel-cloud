@extends('layouts.admin')

@section('title', __('messages.server.title'))

@section('content')
<x-admin.page-header :title="__('messages.server.list_title')" icon="bi-hdd-network">
    <form method="POST" action="{{ route('admin.server.sync') }}" data-loading-label="{{ __('messages.common.loading') }}">
        @csrf
        <button type="submit" class="btn btn-outline-primary btn-icon">
            <i class="bi bi-arrow-repeat"></i>
            {{ __('messages.server.sync_btn') }}
        </button>
    </form>
</x-admin.page-header>

@if($error)
    <div class="alert alert-danger d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ $error }}
    </div>
@elseif(!$options)
    <div class="alert alert-warning d-flex align-items-center">
        <i class="bi bi-gear me-2"></i> {{ __('messages.server.config_error') }}
    </div>
@else
    <div class="card shadow-sm">
        <div class="card-header">
            <h2 class="h5 mb-0">{{ __('messages.server.synced_servers') }}</h2>
        </div>
        <div class="card-body">
            @if(empty($servers))
                <div class="empty-state">
                    <i class="bi bi-hdd-network"></i>
                    <span>{{ __('messages.server.no_servers') }}</span>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('messages.server.name') }}</th>
                                <th>{{ __('messages.server.address') }}</th>
                                <th>{{ __('messages.server.port') }}</th>
                                <th>{{ __('messages.server.type') }}</th>
                                <th>{{ __('messages.server.icon') }}</th>
                                <th class="text-end">{{ __('messages.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($servers as $server)
                                <tr>
                                    <td><strong>{{ $server['name'] }}</strong></td>
                                    <td><code>{{ $server['address'] }}</code></td>
                                    <td>{{ $server['port'] }}</td>
                                    <td><span class="badge text-bg-info">{{ $server['type'] }}</span></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($server['icon_url'])
                                                <img src="{{ $server['icon_url'] }}"
                                                     alt="{{ __('messages.server.icon') }}"
                                                     class="rounded"
                                                     width="40"
                                                     height="40"
                                                     style="object-fit: cover;">
                                                @if($server['icon_local'])
                                                    <span class="badge text-bg-success" title="{{ __('messages.server.icon_local') }}">
                                                        <i class="bi bi-device-hdd"></i>
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-secondary">{{ __('messages.common.none') }}</span>
                                            @endif

                                            <form action="{{ route('admin.server.updateIcon', $server['id']) }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <label class="btn btn-sm btn-outline-secondary btn-square" title="{{ __('messages.server.upload_icon') }}">
                                                    <i class="bi bi-upload"></i>
                                                    <input type="file" name="icon" class="d-none" accept="image/*" onchange="this.form.submit()">
                                                </label>
                                            </form>

                                            @if($server['icon_local'])
                                                <form action="{{ route('admin.server.deleteIcon', $server['id']) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-square" title="{{ __('messages.server.delete_icon') }}" data-confirm="{{ __('messages.server.confirm_delete_icon') }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        @if(!($defaultServers[$server['id']] ?? false))
                                            <form method="POST"
                                                  action="{{ route('admin.server.set-default') }}"
                                                  class="d-inline"
                                                  data-loading-label="{{ __('messages.server.processing') }}">
                                                @csrf
                                                <input type="hidden" name="server_id" value="{{ $server['id'] }}">
                                                <button type="submit" class="btn btn-sm btn-primary btn-icon" data-confirm="{{ __('messages.server.confirm_default') }}">
                                                    <i class="bi bi-star"></i>
                                                    {{ __('messages.server.set_default') }}
                                                </button>
                                            </form>
                                        @else
                                            <span class="badge text-bg-success">
                                                <i class="bi bi-check-circle-fill me-1"></i>{{ __('messages.server.is_default') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    {{ __('messages.server.default_info') }}
                </div>
            @endif
        </div>
    </div>
@endif
@endsection
