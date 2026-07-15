@extends('layouts.admin')

@section('title', $article ? __('messages.news.edit_title') : __('messages.news.create_title'))
@section('page-title', $article ? __('messages.news.edit_title') : __('messages.news.create_title'))

@section('content')
<div class="mb-3">
    <a href="{{ route('admin.news.index') }}" class="btn btn-outline-secondary btn-sm btn-icon">
        <i class="bi bi-arrow-left"></i>
        {{ __('messages.news.back') }}
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ $article ? route('admin.news.update', $article) : route('admin.news.store') }}"
              method="POST" enctype="multipart/form-data" id="news-form">
            @csrf
            @if ($article)
                @method('PUT')
            @endif

            <div class="mb-3">
                <label for="title" class="form-label">{{ __('messages.news.article_title') }}</label>
                <input type="text" class="form-control" id="title" name="title"
                       value="{{ old('title', $article?->title) }}" maxlength="255" required>
            </div>

            <div class="mb-3">
                <label class="form-label">{{ __('messages.news.content') }}</label>
                <div class="btn-toolbar border rounded-top p-2 bg-body-tertiary gap-2" role="toolbar">
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" data-command="bold" title="{{ __('messages.news.bold') }}">
                            <i class="bi bi-type-bold"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-command="italic" title="{{ __('messages.news.italic') }}">
                            <i class="bi bi-type-italic"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-command="underline" title="{{ __('messages.news.underline') }}">
                            <i class="bi bi-type-underline"></i>
                        </button>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" data-command="insertUnorderedList" title="{{ __('messages.news.list') }}">
                            <i class="bi bi-list-ul"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="insert-link" title="{{ __('messages.news.link') }}">
                            <i class="bi bi-link-45deg"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-command="removeFormat" title="{{ __('messages.news.clear_format') }}">
                            <i class="bi bi-eraser"></i>
                        </button>
                    </div>
                </div>
                <div id="editor" contenteditable="true" class="form-control border-top-0 rounded-top-0"
                     style="min-height: 220px; overflow-y: auto;">{!! old('content', $article?->content) !!}</div>
                <textarea class="d-none" id="content" name="content" required>{{ old('content', $article?->content) }}</textarea>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="author" class="form-label">{{ __('messages.news.author') }}</label>
                    <input type="text" class="form-control" id="author" name="author"
                           value="{{ old('author', $article?->author) }}" maxlength="100">
                </div>
                <div class="col-md-4">
                    <label for="published_at" class="form-label">{{ __('messages.news.published_at') }}</label>
                    <input type="datetime-local" class="form-control" id="published_at" name="published_at"
                           value="{{ old('published_at', $article?->published_at?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}">
                </div>
                <div class="col-md-4">
                    <label for="image" class="form-label">{{ __('messages.news.image') }}</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
                    <div class="form-text">{{ __('messages.news.image_help') }}</div>
                    @if ($article?->image)
                        <img src="{{ asset('storage/' . $article->image) }}" alt=""
                             class="rounded mt-2 object-fit-cover" style="width: 120px; height: 72px;">
                    @endif
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-icon">
                <i class="bi bi-save"></i>
                {{ $article ? __('messages.common.update') : __('messages.news.publish') }}
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const editor = document.getElementById('editor');
        const content = document.getElementById('content');
        const form = document.getElementById('news-form');

        document.querySelectorAll('[data-command]').forEach((button) => {
            button.addEventListener('click', () => {
                document.execCommand(button.dataset.command, false);
                editor.focus();
            });
        });

        document.getElementById('insert-link').addEventListener('click', () => {
            const url = window.prompt(@js(__('messages.news.link_prompt')), 'https://');
            if (url) {
                document.execCommand('createLink', false, url);
                editor.focus();
            }
        });

        const synchronize = () => {
            content.value = editor.innerHTML.trim();
        };

        editor.addEventListener('input', synchronize);
        form.addEventListener('submit', synchronize);
        synchronize();
    });
</script>
@endsection
