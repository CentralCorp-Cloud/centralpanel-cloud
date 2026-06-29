@props(['fixed' => false])

@php
    $messages = [
        'success' => ['class' => 'success', 'icon' => 'bi-check-circle-fill'],
        'error' => ['class' => 'danger', 'icon' => 'bi-exclamation-triangle-fill'],
        'warning' => ['class' => 'warning', 'icon' => 'bi-exclamation-triangle-fill'],
        'info' => ['class' => 'info', 'icon' => 'bi-info-circle-fill'],
    ];
@endphp

<div @class(['flash-stack', 'flash-stack-fixed' => $fixed])>
    @foreach($messages as $key => $message)
        @if(session($key))
            <div class="alert alert-{{ $message['class'] }} alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi {{ $message['icon'] }} me-2"></i>
                {{ session($key) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('messages.common.close') }}"></button>
            </div>
        @endif
    @endforeach

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <div class="fw-semibold mb-2">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ __('messages.common.errors_occurred') }}
            </div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="{{ __('messages.common.close') }}"></button>
        </div>
    @endif
</div>
