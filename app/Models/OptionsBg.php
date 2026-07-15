<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionsBg extends Model
{
    protected $fillable = [
        'role_id',
        'image_path',
        'video_url',
        'role_name',
        'instance_id',
    ];

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }
}
