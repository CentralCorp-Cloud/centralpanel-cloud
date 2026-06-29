@extends('layouts.admin')

@section('title', __('messages.users.title'))

@section('content')
<x-admin.page-header :title="__('messages.users.header')" icon="bi-people">
    <a class="btn btn-primary btn-icon" href="{{ route('admin.users.create') }}">
        <i class="bi bi-person-plus"></i>
        {{ __('messages.users.add_user') }}
    </a>
</x-admin.page-header>

<div class="card shadow-sm">
    <div class="card-header">
        <h2 class="h5 mb-0">{{ __('messages.users.list') }}</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>{{ __('messages.users.id') }}</th>
                        <th>{{ __('messages.users.name') }}</th>
                        <th>{{ __('messages.users.email') }}</th>
                        <th class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary btn-icon">
                                    <i class="bi bi-pencil"></i>
                                    {{ __('messages.common.edit') }}
                                </a>
                                <form action="{{ route('admin.users.delete', $user->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" data-confirm="{{ __('messages.users.confirm_delete_user') }}">
                                        <i class="bi bi-trash"></i>
                                        {{ __('messages.common.delete') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
