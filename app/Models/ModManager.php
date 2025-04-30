<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModManager extends Model
{
    protected $fillable = [
        'name',
        'enabled'
    ];

    public function eggs()
    {
        return $this->belongsToMany(\App\Models\Egg::class);
    }
}
