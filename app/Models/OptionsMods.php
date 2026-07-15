<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionsMods extends Model
{
    protected $table = 'mods';

    protected $fillable = ['file', 'name', 'description', 'icon', 'optional', 'recommended', 'instance_id'];

    protected $casts = [
        'optional' => 'boolean',
        'recommended' => 'boolean',
    ];

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }
}
