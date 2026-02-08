<!DOCTYPE html>
<html lang="fr" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - CentralCorp Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: #212529;
            min-height: 100vh;
        }

        .install-card {
            max-width: 560px;
        }

        .step-badge {
            width: 28px;
            height: 28px;
            font-size: 0.8rem;
        }

        .form-control,
        .form-select {
            background-color: #2b3035;
            border-color: #495057;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: #343a40;
            border-color: #3b7ddd;
            box-shadow: 0 0 0 0.2rem rgba(59, 125, 221, .25);
        }

        .section-card {
            background-color: #2b3035;
        }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center py-4">
    <div class="container">
        <div class="install-card mx-auto">
            <!-- Header -->
            <div class="text-center mb-4">
                <img src="https://centralcorp.github.io/img/panel.png"
                    style="width: 100%; max-width: 250px; height: auto">
                <p class="text-secondary small mb-0">Assistant d'installation</p>
            </div>

            <!-- Main Card -->
            <div class="card bg-dark border-secondary">
                <div class="card-body p-4">
                    <!-- Steps -->
                    <div class="d-flex justify-content-center align-items-center gap-3 mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <span
                                class="step-badge bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-semibold">1</span>
                            <span class="text-white small">Configuration</span>
                        </div>
                        <div class="border-top border-secondary" style="width: 40px;"></div>
                        <div class="d-flex align-items-center gap-2">
                            <span
                                class="step-badge bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-semibold">2</span>
                            <span class="text-secondary small">Terminé</span>
                        </div>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger py-2 small">
                            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('install.store') }}" id="installForm">
                        @csrf

                        <!-- Database Section -->
                        <div class="section-card rounded p-3 mb-3">
                            <h6 class="text-white mb-3">
                                <i class="bi bi-database text-primary me-2"></i>Base de données
                            </h6>

                            <div class="alert alert-primary py-2 small mb-3">
                                <i class="bi bi-lightbulb me-1"></i>
                                SQLite est recommandé pour une installation rapide.
                            </div>

                            <div class="mb-3">
                                <label for="type" class="form-label small text-secondary">Type de base de
                                    données</label>
                                <select class="form-select" id="type" name="type" required>
                                    <option value="sqlite" {{ old('type', 'sqlite') == 'sqlite' ? 'selected' : '' }}>
                                        SQLite (Recommandé)</option>
                                    @foreach($databaseDrivers as $key => $name)
                                        @if($key !== 'sqlite')
                                            <option value="{{ $key }}" {{ old('type') == $key ? 'selected' : '' }}>{{ $name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>

                            <div id="database-config" style="display: none;">
                                <div class="row g-2 mb-2">
                                    <div class="col-8">
                                        <label for="host" class="form-label small text-secondary">Hôte</label>
                                        <input type="text" class="form-control" id="host" name="host"
                                            value="{{ old('host', 'localhost') }}">
                                    </div>
                                    <div class="col-4">
                                        <label for="port" class="form-label small text-secondary">Port</label>
                                        <input type="number" class="form-control" id="port" name="port"
                                            value="{{ old('port', '3306') }}">
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label for="database" class="form-label small text-secondary">Nom de la base</label>
                                    <input type="text" class="form-control" id="database" name="database"
                                        value="{{ old('database') }}" placeholder="centralcorp_panel">
                                </div>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label for="user" class="form-label small text-secondary">Utilisateur</label>
                                        <input type="text" class="form-control" id="user" name="user"
                                            value="{{ old('user') }}" placeholder="root">
                                    </div>
                                    <div class="col-6">
                                        <label for="db_password" class="form-label small text-secondary">Mot de
                                            passe</label>
                                        <input type="password" class="form-control" id="db_password" name="db_password">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Admin Section -->
                        <div class="section-card rounded p-3 mb-4">
                            <h6 class="text-white mb-3">
                                <i class="bi bi-person-badge text-primary me-2"></i>Compte Administrateur
                            </h6>

                            <div class="mb-2">
                                <label for="name" class="form-label small text-secondary">Nom d'utilisateur</label>
                                <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}"
                                    required placeholder="admin">
                            </div>
                            <div class="mb-2">
                                <label for="email" class="form-label small text-secondary">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="{{ old('email') }}" required placeholder="admin@example.com">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label for="password" class="form-label small text-secondary">Mot de passe</label>
                                    <input type="password" class="form-control" id="password" name="password" required
                                        placeholder="••••••••">
                                </div>
                                <div class="col-6">
                                    <label for="password_confirmation"
                                        class="form-label small text-secondary">Confirmation</label>
                                    <input type="password" class="form-control" id="password_confirmation"
                                        name="password_confirmation" required placeholder="••••••••">
                                </div>
                            </div>
                            <small class="text-secondary">Minimum 8 caractères</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            <i class="bi bi-rocket-takeoff me-2"></i>Installer CentralCorp Panel
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const typeSelect = document.getElementById('type');
        const dbConfig = document.getElementById('database-config');

        function toggleDbConfig() {
            dbConfig.style.display = typeSelect.value === 'sqlite' ? 'none' : 'block';
        }

        typeSelect.addEventListener('change', toggleDbConfig);
        toggleDbConfig();

        document.getElementById('installForm').addEventListener('submit', function () {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Installation...';
        });
    </script>
</body>

</html>
