@extends('layouts.install')

@section('title', 'Installation terminée - CentralCorp Panel')
@section('subtitle', 'Installation terminée')

@section('content')
<div class="text-center">
    <div class="panel-title-icon mx-auto mb-3 text-success">
        <i class="bi bi-check-lg"></i>
    </div>
    <h2 class="h4 fw-bold mb-2">Installation réussie</h2>
    <p class="text-secondary mb-4">CentralCorp Panel est installé et prêt à être utilisé.</p>
</div>

<div class="panel-muted-surface p-3 mb-4">
    <div class="d-flex align-items-center gap-3 mb-3">
        <span class="panel-title-icon"><i class="bi bi-database-check"></i></span>
        <div>
            <div class="fw-semibold">Base de données configurée</div>
            <div class="text-secondary small">Créée et initialisée</div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3 mb-3">
        <span class="panel-title-icon"><i class="bi bi-person-check"></i></span>
        <div>
            <div class="fw-semibold">Compte administrateur créé</div>
            <div class="text-secondary small">Prêt à l'emploi</div>
        </div>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span class="panel-title-icon"><i class="bi bi-rocket-takeoff"></i></span>
        <div>
            <div class="fw-semibold">Panel opérationnel</div>
            <div class="text-secondary small">Prêt à utiliser</div>
        </div>
    </div>
</div>

<a href="{{ url('/') }}" class="btn btn-primary btn-icon w-100 mb-3">
    <i class="bi bi-box-arrow-in-right"></i>
    Accéder au panel
</a>

<p class="text-secondary small text-center mb-0">
    Redirection dans <span id="countdown" class="text-primary fw-semibold">5</span>s...
</p>
@endsection

@push('scripts')
<script>
    let seconds = 5;
    const countdown = setInterval(() => {
        seconds--;
        document.getElementById('countdown').textContent = seconds;
        if (seconds <= 0) {
            clearInterval(countdown);
            window.location.href = '{{ url('/') }}';
        }
    }, 1000);
</script>
@endpush
