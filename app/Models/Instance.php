<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Instance extends Model
{
    protected $table = 'instances';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'server_ip',
        'server_port',
        'server_name',
        'server_icon',
        'server_icon_url',
        'minecraft_version',
        'loader_type',
        'loader_build_version',
        'loader_activation',
        'background_default',
        'rpc_details_override',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'loader_activation' => 'boolean',
    ];

    public function mods()
    {
        return $this->hasMany(OptionsMods::class, 'instance_id');
    }

    public function whitelist()
    {
        return $this->hasMany(OptionsWhitelist::class, 'instance_id');
    }

    public function whitelistRoles()
    {
        return $this->hasMany(OptionsWhitelistRole::class, 'instance_id');
    }

    public function ignoredFolders()
    {
        return $this->hasMany(OptionsIgnore::class, 'instance_id');
    }

    public function backgrounds()
    {
        return $this->hasMany(OptionsBg::class, 'instance_id');
    }

    /**
     * Get the server icon URL (local prioritized over remote)
     */
    public function getServerIconFullUrlAttribute(): ?string
    {
        $localIcon = $this->attributes['server_icon'] ?? null;
        $remoteIcon = $this->attributes['server_icon_url'] ?? null;

        if ($localIcon) {
            return asset('storage/' . $localIcon);
        }

        if ($remoteIcon) {
            if (filter_var($remoteIcon, FILTER_VALIDATE_URL)) {
                return $remoteIcon;
            }

            $options = OptionsGeneral::first();
            if ($options && $options->azuriom_url) {
                return rtrim($options->azuriom_url, '/') . '/storage/' . ltrim(str_replace('storage/', '', $remoteIcon), '/');
            }
        }

        return null;
    }
}
