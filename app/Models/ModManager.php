<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModManager extends Model
{
    protected $fillable = [
        'name',
        'enabled'
    ];

    /**
     * Define a custom many-to-many relationship with eggs.
     */
    public function eggs()
    {
        return $this->belongsToMany(
            \App\Models\Egg::class,
            'egg_mod_manager',
            'mod_manager_id',
            'egg_id'
        );
    }
}
