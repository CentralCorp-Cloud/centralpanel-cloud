<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionsServer extends Model
{
    use HasFactory;

    protected $table = 'options_server';
    protected $fillable = [
        'server_id',
        'server_name',
        'server_ip',
        'server_port',
        'icon',
        'icon_local',
        'type',
        'is_default'
    ];

    protected $casts = [
        'is_default' => 'boolean'
    ];

    /**
     * Retourne l'URL de l'icône (locale prioritaire sur distante)
     */
    public function getIconUrlAttribute(): ?string
    {
        if ($this->icon_local) {
            return asset('storage/' . $this->icon_local);
        }

        if ($this->icon) {
            static $azuriomUrl = null;
            $azuriomUrl ??= OptionsGeneral::value('azuriom_url');
            if ($azuriomUrl) {
                return rtrim($azuriomUrl, '/') . '/storage/' . ltrim(str_replace('storage/', '', $this->icon), '/');
            }
        }

        return null;
    }
}
