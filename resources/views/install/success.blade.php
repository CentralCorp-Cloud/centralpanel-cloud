<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Terminée - CentralCorp Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background-color: #212529; min-height: 100vh; }
        .install-card { max-width: 560px; }
        .step-badge { width: 28px; height: 28px; font-size: 0.8rem; }
        .feature-item { background-color: #2b3035; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-4">
    <div class="container">
        <div class="install-card mx-auto">
            <!-- Header -->
            <div class="text-center mb-4">
                <div class="bg-success rounded d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-check-lg text-white fs-4"></i>
                </div>
                <h1 class="h4 fw-bold text-white mb-1">CentralCorp Panel</h1>
                <p class="text-secondary small mb-0">Installation terminée</p>
            </div>
            
            <!-- Main Card -->
            <div class="card bg-dark border-secondary">
                <div class="card-body p-4 text-center">
                    <!-- Steps -->
                    <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <span class="step-badge bg-success text-white rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-check"></i>
                            </span>
                            <span class="text-success small">Configuration</span>
                        </div>
                        <div class="border-top border-success" style="width: 40px;"></div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="step-badge bg-success text-white rounded-circle d-flex align-items-center justify-content-center">
                                <i class="bi bi-check"></i>
                            </span>
                            <span class="text-success small">Terminé</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                            <i class="bi bi-check-lg text-white fs-2"></i>
                        </div>
                        <h2 class="h5 text-white mb-2">Installation Réussie !</h2>
                        <p class="text-secondary small mb-0">CentralCorp Panel a été installé avec succès.</p>
                    </div>
                    
                    @if(session('success'))
                        <div class="alert alert-success py-2 small mb-4">
                            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        </div>
                    @endif
                    
                    <!-- Features -->
                    <div class="text-start mb-4">
                        <div class="feature-item rounded p-3 mb-2 d-flex align-items-center gap-3">
                            <div class="bg-primary rounded d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="bi bi-database-check text-white"></i>
                            </div>
                            <div>
                                <div class="text-white small fw-medium">Base de données configurée</div>
                                <div class="text-secondary" style="font-size: 0.75rem;">Créée et initialisée</div>
                            </div>
                        </div>
                        <div class="feature-item rounded p-3 mb-2 d-flex align-items-center gap-3">
                            <div class="bg-primary rounded d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="bi bi-person-check text-white"></i>
                            </div>
                            <div>
                                <div class="text-white small fw-medium">Compte administrateur créé</div>
                                <div class="text-secondary" style="font-size: 0.75rem;">Prêt à l'emploi</div>
                            </div>
                        </div>
                        <div class="feature-item rounded p-3 d-flex align-items-center gap-3">
                            <div class="bg-primary rounded d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="bi bi-rocket-takeoff text-white"></i>
                            </div>
                            <div>
                                <div class="text-white small fw-medium">Panel opérationnel</div>
                                <div class="text-secondary" style="font-size: 0.75rem;">Prêt à utiliser</div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="{{ url('/') }}" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Accéder au Panel
                    </a>
                    
                    <p class="text-secondary small mb-0">
                        Redirection dans <span id="countdown" class="text-primary fw-medium">5</span>s...
                    </p>
                </div>
            </div>
        </div>
    </div>

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
</body>
</html>
