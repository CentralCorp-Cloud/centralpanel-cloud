<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionsWhitelist extends Model
{
    use HasFactory;

    protected $table = 'whitelist';
    protected $fillable = ['users', 'instance_id'];

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }
}
