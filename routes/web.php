<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsExportController;
use App\Http\Controllers\Admin\UpdateController;
use App\Http\Controllers\AdminConfigController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminInstanceController;
use App\Http\Controllers\AdminNewsController;
use App\Http\Controllers\AdminRpcController;
use App\Http\Controllers\AdminSecurityController;
use App\Http\Controllers\AdminUIController;
use App\Http\Controllers\api\ApiController;
use App\Http\Controllers\api\FileController;
use App\Http\Controllers\api\ModController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\users\AdminUserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Auth::routes(['register' => false]);

Route::get('/', [PanelController::class, 'root']);

Route::get('/install', [InstallController::class, 'showDatabase'])->name('install.database');
Route::post('/install', [InstallController::class, 'install'])->name('install.store');
Route::get('/install/finish', [InstallController::class, 'finish'])->name('install.finish');

Route::prefix('admin')->middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.index');

    Route::get('/config', [AdminConfigController::class, 'show'])->name('admin.config');
    Route::post('/config', [AdminConfigController::class, 'update'])->name('admin.config.update');

    Route::get('/general', [AdminController::class, 'general'])->name('admin.general');
    Route::post('/general/update', [AdminController::class, 'updateGeneral'])->name('admin.general.update');

    Route::get('/news', [AdminNewsController::class, 'index'])->name('admin.news.index');
    Route::get('/news/create', [AdminNewsController::class, 'create'])->name('admin.news.create');
    Route::post('/news', [AdminNewsController::class, 'store'])->name('admin.news.store');
    Route::get('/news/{news}/edit', [AdminNewsController::class, 'edit'])->name('admin.news.edit');
    Route::put('/news/{news}', [AdminNewsController::class, 'update'])->name('admin.news.update');
    Route::delete('/news/{news}', [AdminNewsController::class, 'destroy'])->name('admin.news.destroy');

    Route::get('/security', [AdminSecurityController::class, 'show'])->name('admin.security');
    Route::post('/security/update', [AdminSecurityController::class, 'update'])->name('admin.security.update');

    Route::get('/ui', [AdminUIController::class, 'show'])->name('admin.ui');
    Route::post('/ui/update', [AdminUIController::class, 'update'])->name('admin.ui.update');

    Route::get('/rpc', [AdminRpcController::class, 'show'])->name('admin.rpc');
    Route::post('/rpc/update', [AdminRpcController::class, 'update'])->name('admin.rpc.update');

    Route::get('/instances/loader/builds', [AdminInstanceController::class, 'getForgeBuilds'])->name('admin.instances.loader.builds');
    Route::get('/instances/loader/fabric-versions', [AdminInstanceController::class, 'getFabricVersions'])->name('admin.instances.loader.fabric-versions');
    Route::get('/instances/fetch-servers', [AdminInstanceController::class, 'fetchServers'])->name('admin.instances.fetchServers');
    Route::get('/instances', [AdminInstanceController::class, 'index'])->name('admin.instances.index');
    Route::get('/instances/create', [AdminInstanceController::class, 'create'])->name('admin.instances.create');
    Route::post('/instances', [AdminInstanceController::class, 'store'])->name('admin.instances.store');
    Route::get('/instances/{id}/edit', [AdminInstanceController::class, 'edit'])->name('admin.instances.edit');
    Route::put('/instances/{id}', [AdminInstanceController::class, 'update'])->name('admin.instances.update');
    Route::delete('/instances/{id}', [AdminInstanceController::class, 'destroy'])->name('admin.instances.destroy');
    Route::post('/instances/{id}/set-default', [AdminInstanceController::class, 'setDefault'])->name('admin.instances.setDefault');
    Route::delete('/instances/{id}/icon', [AdminInstanceController::class, 'deleteIcon'])->name('admin.instances.deleteIcon');

    Route::get('/instances/{instanceId}/whitelist', [AdminInstanceController::class, 'whitelistIndex'])->name('admin.instances.whitelist');
    Route::post('/instances/{instanceId}/whitelist', [AdminInstanceController::class, 'whitelistStore'])->name('admin.instances.whitelist.store');
    Route::delete('/instances/{instanceId}/whitelist/user/{id}', [AdminInstanceController::class, 'whitelistDestroyUser'])->name('admin.instances.whitelist.destroyUser');
    Route::delete('/instances/{instanceId}/whitelist/role/{id}', [AdminInstanceController::class, 'whitelistDestroyRole'])->name('admin.instances.whitelist.destroyRole');
    Route::get('/instances/{instanceId}/whitelist/fetch-users', [AdminInstanceController::class, 'whitelistFetchUsers'])->name('admin.instances.whitelist.fetchUsers');
    Route::get('/instances/{instanceId}/whitelist/fetch-roles', [AdminInstanceController::class, 'whitelistFetchRoles'])->name('admin.instances.whitelist.fetchRoles');

    Route::get('/instances/{instanceId}/ignore', [AdminInstanceController::class, 'ignoreIndex'])->name('admin.instances.ignore');
    Route::post('/instances/{instanceId}/ignore', [AdminInstanceController::class, 'ignoreStore'])->name('admin.instances.ignore.store');
    Route::delete('/instances/{instanceId}/ignore/{id}', [AdminInstanceController::class, 'ignoreDestroy'])->name('admin.instances.ignore.destroy');

    Route::get('/instances/{instanceId}/mods', [AdminInstanceController::class, 'modsIndex'])->name('admin.instances.mods');
    Route::post('/instances/{instanceId}/mods/add', [AdminInstanceController::class, 'modsAdd'])->name('admin.instances.mods.add');
    Route::put('/instances/{instanceId}/mods/update/{modId}', [AdminInstanceController::class, 'modsUpdate'])->name('admin.instances.mods.update');
    Route::post('/instances/{instanceId}/mods/delete/{id}', [AdminInstanceController::class, 'modsDelete'])->name('admin.instances.mods.delete');

    Route::get('/instances/{instanceId}/bg', [AdminInstanceController::class, 'bgIndex'])->name('admin.instances.bg');
    Route::post('/instances/{instanceId}/bg/update', [AdminInstanceController::class, 'bgUpdate'])->name('admin.instances.bg.update');
    Route::delete('/instances/{instanceId}/bg/{roleId}', [AdminInstanceController::class, 'bgDestroy'])->name('admin.instances.bg.destroy');
    Route::get('/instances/{instanceId}/bg/fetch-roles', [AdminInstanceController::class, 'bgFetchRoles'])->name('admin.instances.bg.fetchRoles');

    Route::get('/instances/{instanceId}/files', [AdminInstanceController::class, 'fileManager'])->name('admin.instances.files');

    Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('admin.users.create');
    Route::post('/users/add', [AdminUserController::class, 'add'])->name('admin.users.add');
    Route::delete('/users/delete/{id}', [AdminUserController::class, 'delete'])->name('admin.users.delete');
    Route::get('/users/edit/{id}', [AdminUserController::class, 'edit'])->name('admin.users.edit');
    Route::put('/users/update/{id}', [AdminUserController::class, 'update'])->name('admin.users.update');

    Route::get('/settings/export', [SettingsExportController::class, 'export'])->name('admin.settings.export');
    Route::post('/settings/import', [SettingsExportController::class, 'import'])->name('admin.settings.import');

    Route::get('/update', [UpdateController::class, 'index'])->name('admin.update');
    Route::post('/update', [UpdateController::class, 'update'])->name('admin.update.run');
    Route::post('/update/cache', [UpdateController::class, 'clearCache'])->name('admin.update.cache');
});

Route::prefix('utils')->group(function () {
    Route::get('/api', [ApiController::class, 'getOptions']);
    Route::get('/mods', [ModController::class, 'getMods']);
});

Route::get('/data', [FileController::class, 'getFiles']);
Route::get('lang/{locale}', [App\Http\Controllers\LanguageController::class, 'switch'])->name('lang.switch');
