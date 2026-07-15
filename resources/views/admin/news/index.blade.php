@extends('layouts.admin')

@section('title', __('messages.news.title'))
@section('page-title', __('messages.news.title'))

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <p class="text-muted mb-0">{{ __('messages.news.description') }}</p>
    </div>
    <a href="{{ route('admin.news.create') }}" class="btn btn-primary btn-icon">
        <i class="bi bi-plus-circle"></i>
        {{ __('messages.news.create') }}
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('messages.news.article') }}</th>
                        <th>{{ __('messages.news.author') }}</th>
                        <th>{{ __('messages.news.published_at') }}</th>
                        <th class="text-end">{{ __('messages.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($news as $article)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    @if ($article->image)
                                        <img src="{{ asset('storage/' . $article->image) }}" alt=""
                                             class="rounded object-fit-cover" width="56" height="42">
                                    @endif
                                    <strong>{{ $article->title }}</strong>
                                </div>
                            </td>
                            <td>{{ $article->author ?: '—' }}</td>
                            <td>{{ $article->published_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.news.edit', $article) }}"
                                   class="btn btn-sm btn-outline-primary" title="{{ __('messages.common.edit') }}">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('admin.news.destroy', $article) }}" method="POST"
                                      class="d-inline"
                                      onsubmit="return confirm(@js(__('messages.news.delete_confirm')))" >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            title="{{ __('messages.common.delete') }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-5">
                                <i class="bi bi-newspaper fs-2 d-block mb-2"></i>
                                {{ __('messages.news.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
