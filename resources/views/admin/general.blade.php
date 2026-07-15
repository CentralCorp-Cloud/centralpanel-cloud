@extends('layouts.admin')

@section('title', __('messages.general.title'))
@section('page-title', __('messages.general.title'))

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.general.update') }}" method="POST">
            @csrf

            @php
                $authMode = $options->auth_mode ?? 'azuriom';
            @endphp

            <div class="panel-muted-surface p-3 mb-4">
                <h2 class="panel-section-title"><i class="bi bi-toggles"></i>{{ __('messages.general.features') }}</h2>
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    @php
                        $switches = [
                            ['id' => 'mods_enabled', 'label' => __('messages.general.mods_enabled')],
                            ['id' => 'file_verification', 'label' => __('messages.general.file_verification')],
                            ['id' => 'embedded_java', 'label' => __('messages.general.embedded_java')],
                        ];

                        if ($authMode === 'azuriom') {
                            $switches[] = ['id' => 'email_verified', 'label' => __('messages.general.email_verified')];
                            $switches[] = ['id' => 'role_display', 'label' => __('messages.general.role_display')];
                            $switches[] = ['id' => 'money_display', 'label' => __('messages.general.money_display')];
                        }
                    @endphp

                    @foreach ($switches as $switch)
                        <div class="col">
                            <div class="form-check form-switch">
                                <input type="hidden" name="{{ $switch['id'] }}" value="0">
                                <input type="checkbox"
                                       class="form-check-input"
                                       id="{{ $switch['id'] }}"
                                       name="{{ $switch['id'] }}"
                                       value="1"
                                       {{ old($switch['id'], $options->{$switch['id']}) ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $switch['id'] }}">{{ $switch['label'] }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="panel-muted-surface p-3 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">
                    <h2 class="panel-section-title mb-0"><i class="bi bi-newspaper"></i>{{ __('messages.general.news') }}</h2>
                    <a href="{{ route('admin.news.index') }}" class="btn btn-sm btn-outline-primary btn-icon">
                        <i class="bi bi-pencil-square"></i>{{ __('messages.general.manage_news') }}
                    </a>
                </div>

                <div class="row g-3">
                    <div class="col-lg-7">
                        <label class="form-label fw-semibold">{{ __('messages.general.news_source') }}</label>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="news_mode" id="news_builtin"
                                   value="builtin" @checked(old('news_mode', $options->news_mode ?? 'rss') === 'builtin')>
                            <label class="form-check-label" for="news_builtin">
                                <strong>{{ __('messages.general.news_builtin') }}</strong>
                                <span class="text-muted">— {{ __('messages.general.news_builtin_desc') }}</span>
                            </label>
                        </div>

                        <div class="form-check mb-2">
                            <input class="form-check-input" type="radio" name="news_mode" id="news_rss"
                                   value="rss" @checked(old('news_mode', $options->news_mode ?? 'rss') === 'rss')>
                            <label class="form-check-label" for="news_rss">
                                <strong>{{ __('messages.general.news_rss') }}</strong>
                                <span class="text-muted">— {{ __('messages.general.news_rss_desc') }}</span>
                            </label>
                        </div>

                        @if ($azuriomNewsAvailable)
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="news_mode" id="news_azuriom"
                                       value="azuriom" @checked(old('news_mode', $options->news_mode ?? 'rss') === 'azuriom')>
                                <label class="form-check-label" for="news_azuriom">
                                    <strong>{{ __('messages.general.news_azuriom') }}</strong>
                                    <span class="text-muted">— {{ __('messages.general.news_azuriom_desc') }}</span>
                                </label>
                            </div>
                        @else
                            <div class="alert alert-info py-2 mt-3 mb-0">
                                <i class="bi bi-info-circle me-1"></i>
                                {!! __('messages.general.news_azuriom_unavailable', ['url' => route('admin.config')]) !!}
                            </div>
                        @endif
                    </div>

                    <div class="col-lg-5" id="rss_url_group">
                        <label for="news_rss_url" class="form-label">{{ __('messages.general.news_rss_url') }}</label>
                        <input type="url" class="form-control" id="news_rss_url" name="news_rss_url"
                               placeholder="https://example.com/feed.xml"
                               value="{{ old('news_rss_url', $options->news_rss_url ?? '') }}">
                        <div class="form-text">{{ __('messages.general.news_rss_help') }}</div>
                    </div>
                </div>
            </div>

            <div class="panel-muted-surface p-3 mb-4">
                <h2 class="panel-section-title"><i class="bi bi-cpu"></i>{{ __('messages.general.technical_settings') }}</h2>

                <div class="mb-3">
                    <label for="game_folder_name" class="form-label">{{ __('messages.general.game_folder_name') }}</label>
                    <input type="text"
                           class="form-control"
                           id="game_folder_name"
                           name="game_folder_name"
                           placeholder="centralcorp"
                           value="{{ old('game_folder_name', $options->game_folder_name) }}">
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="default_min_ram" class="form-label">{{ __('messages.general.min_ram') }}</label>
                        <input type="number"
                               class="form-control"
                               id="default_min_ram"
                               name="min_ram"
                               min="512"
                               max="65536"
                               value="{{ old('min_ram', $options->min_ram ?? 2048) }}">
                    </div>

                    <div class="col-md-6">
                        <label for="default_max_ram" class="form-label">{{ __('messages.general.max_ram') }}</label>
                        <input type="number"
                               class="form-control"
                               id="default_max_ram"
                               name="max_ram"
                               min="512"
                               max="65536"
                               value="{{ old('max_ram', $options->max_ram ?? 4096) }}">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-icon">
                <i class="bi bi-save"></i>
                {{ __('messages.common.update') }}
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const rssRadio = document.getElementById('news_rss');
        const rssUrlGroup = document.getElementById('rss_url_group');
        const rssUrl = document.getElementById('news_rss_url');
        const newsRadios = document.querySelectorAll('input[name="news_mode"]');

        const toggleRssUrl = () => {
            const enabled = rssRadio.checked;
            rssUrlGroup.hidden = !enabled;
            rssUrl.required = enabled;
        };

        newsRadios.forEach((radio) => radio.addEventListener('change', toggleRssUrl));
        toggleRssUrl();
    });
</script>
@endsection
