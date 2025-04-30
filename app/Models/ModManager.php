<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModManager extends Model
{
    protected $fillable = [
        'name',
        'egg_id',
        'enabled'
    ];

    public function egg()
    {
        return $this->belongsTo(\App\Models\Egg::class);
    }
}
