<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pré-requis - CentralCorp Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background-color: #212529; min-height: 100vh; }
        .install-card { max-width: 600px; }
        .section-card { background-color: #2b3035; }
        .req-item { background-color: #343a40; }
        .req-item.success { border-left: 3px solid #1cbb8c; }
        .req-item.error { border-left: 3px solid #dc3545; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center py-4">
    <div class="container">
        <div class="install-card mx-auto">
            <!-- Header -->
            <div class="text-center mb-4">
                <div class="bg-primary rounded d-inline-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-clipboard-check text-white fs-4"></i>
                </div>
                <h1 class="h4 fw-bold text-white mb-1">CentralCorp Panel</h1>
                <p class="text-secondary small mb-0">Vérification des pré-requis</p>
            </div>
            
            <!-- Main Card -->
            <div class="card bg-dark border-secondary">
                <div class="card-body p-4">
                    
                    <!-- PHP Version -->
                    <div class="section-card rounded p-3 mb-3">
                        <h6 class="text-white mb-3">
                            <i class="bi bi-filetype-php text-primary me-2"></i>Version PHP
                        </h6>
                        <div class="req-item rounded p-2 d-flex justify-content-between align-items-center {{ $requirements['php'] ? 'success' : 'error' }}">
                            <span class="text-white small">
                                PHP {{ \App\Http\Controllers\InstallController::MIN_PHP_VERSION }}+ 
                                <span class="text-primary">({{ \App\Http\Controllers\InstallController::parsePhpVersion() }})</span>
                            </span>
                            @if($requirements['php'])
                                <i class="bi bi-check-circle-fill text-success"></i>
                            @else
                                <i class="bi bi-x-circle-fill text-danger"></i>
                            @endif
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div class="section-card rounded p-3 mb-3">
                        <h6 class="text-white mb-3">
                            <i class="bi bi-folder-check text-primary me-2"></i>Permissions
                        </h6>
                        <div class="req-item rounded p-2 mb-2 d-flex justify-content-between align-items-center {{ $requirements['writable'] ? 'success' : 'error' }}">
                            <span class="text-white small">Dossier racine</span>
                            @if($requirements['writable'])
                                <i class="bi bi-check-circle-fill text-success"></i>
                            @else
                                <i class="bi bi-x-circle-fill text-danger"></i>
                            @endif
                        </div>
                        <div class="req-item rounded p-2 mb-2 d-flex justify-content-between align-items-center {{ $requirements['storage-writable'] ? 'success' : 'error' }}">
                            <span class="text-white small">Dossier storage</span>
                            @if($requirements['storage-writable'])
                                <i class="bi bi-check-circle-fill text-success"></i>
                            @else
                                <i class="bi bi-x-circle-fill text-danger"></i>
                            @endif
                        </div>
                        <div class="req-item rounded p-2 d-flex justify-content-between align-items-center {{ $requirements['bootstrap-writable'] ? 'success' : 'error' }}">
                            <span class="text-white small">Dossier bootstrap/cache</span>
                            @if($requirements['bootstrap-writable'])
                                <i class="bi bi-check-circle-fill text-success"></i>
                            @else
                                <i class="bi bi-x-circle-fill text-danger"></i>
                            @endif
                        </div>
                    </div>

                    <!-- Extensions -->
                    <div class="section-card rounded p-3 mb-4">
                        <h6 class="text-white mb-3">
                            <i class="bi bi-puzzle text-primary me-2"></i>Extensions PHP
                        </h6>
                        <div class="row g-2">
                            @foreach(\App\Http\Controllers\InstallController::REQUIRED_EXTENSIONS as $extension)
                                <div class="col-6">
                                    <div class="req-item rounded p-2 d-flex justify-content-between align-items-center {{ $requirements['extension-' . $extension] ? 'success' : 'error' }}">
                                        <span class="text-white small">{{ $extension }}</span>
                                        @if($requirements['extension-' . $extension])
                                            <i class="bi bi-check-circle-fill text-success"></i>
                                        @else
                                            <i class="bi bi-x-circle-fill text-danger"></i>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @php $allMet = !in_array(false, $requirements, true); @endphp

                    @if($allMet)
                        <div class="alert alert-success py-2 small mb-3">
                            <i class="bi bi-check-circle me-2"></i>Tous les pré-requis sont satisfaits !
                        </div>
                        <a href="{{ route('install.database') }}" class="btn btn-primary w-100">
                            <i class="bi bi-arrow-right me-2"></i>Continuer l'installation
                        </a>
                    @else
                        <div class="alert alert-warning py-2 small mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>Certains pré-requis ne sont pas satisfaits.
                        </div>
                        <button type="button" class="btn btn-warning w-100" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Vérifier à nouveau
                        </button>
                    @endif
                    
                    <p class="text-secondary small text-center mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>Consultez la documentation en cas de problème.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
