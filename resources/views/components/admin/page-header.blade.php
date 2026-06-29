@props([
    'title',
    'subtitle' => null,
    'icon' => null,
])

<div class="panel-page-header">
    <div>
        <div class="panel-title-row">
            @if($icon)
                <span class="panel-title-icon"><i class="bi {{ $icon }}"></i></span>
            @endif
            <h1>{{ $title }}</h1>
        </div>
        @if($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="panel-page-actions">
            {{ $slot }}
        </div>
    @endif
</div>
