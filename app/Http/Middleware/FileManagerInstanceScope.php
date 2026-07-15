<?php

namespace App\Http\Middleware;

use App\Models\Instance;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to scope the laravel-file-manager to an instance-specific disk.
 * 
 * The controller stores the instance name in the session when the user opens
 * the file manager. This middleware reads it and dynamically registers the
 * appropriate disk + overrides the file-manager config before every FM request.
 */
class FileManagerInstanceScope
{
    public function handle(Request $request, Closure $next)
    {
        $instanceName = session('file_manager_instance');
        $instance = is_string($instanceName)
            ? Instance::query()->where('name', $instanceName)->first()
            : null;

        if ($instance) {
            $instanceName = $instance->name;
            $diskName = 'instance_' . $instanceName;

            config([
                "filesystems.disks.{$diskName}" => [
                    'driver' => 'local',
                    'root' => storage_path('app/public/data/' . $instanceName),
                    'url' => config('app.url') . '/storage/data/' . $instanceName,
                    'visibility' => 'public',
                    'throw' => false,
                ],
                'file-manager.diskList' => [$diskName],
                'file-manager.leftDisk' => $diskName,
                'file-manager.rightDisk' => $diskName,
                'file-manager.leftPath' => null,
                'file-manager.rightPath' => null,
            ]);
        }

        return $next($request);
    }
}
