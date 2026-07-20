<?php

use App\Support\SecretFile;

$managed = filter_var(env('PANEL_MANAGED', false), FILTER_VALIDATE_BOOL);
$internalSecretFile = (string) env('CENTRALCLOUD_INTERNAL_SECRET_FILE', '');

return [
    'managed' => $managed,
    'mode' => env('CENTRALPANEL_MODE', $managed ? 'centralcloud' : 'standalone'),
    'cloud_project_id' => env('CLOUD_PROJECT_ID'),
    'runtime_path' => env('PANEL_RUNTIME_PATH', storage_path('runtime')),
    'internal_secret' => $internalSecretFile !== ''
        ? SecretFile::readValue($internalSecretFile, 'CENTRALCLOUD_INTERNAL_SECRET_FILE')
        : null,
];
