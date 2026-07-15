@extends('layouts.admin')

@section('title', __('messages.users.edit_user'))
@section('page-title', __('messages.users.edit_user'))

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">{{ __('messages.users.name') }}</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                       name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">{{ __('messages.users.email') }}</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email"
                       name="email" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="password" class="form-label">{{ __('messages.users.new_password') }}</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                           id="password" name="password" placeholder="{{ __('messages.users.new_password_placeholder') }}">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label for="password_confirmation" class="form-label">{{ __('messages.users.password_confirm') }}</label>
                    <input type="password" class="form-control" id="password_confirmation"
                           name="password_confirmation">
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-icon">
                    <i class="bi bi-save"></i>
                    {{ __('messages.common.update') }}
                </button>
                <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary">{{ __('messages.common.back') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
