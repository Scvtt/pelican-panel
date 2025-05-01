<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $fillable = [
        'name',
        'flag',
        'enabled'
    ];

    /**
     * Define a custom many-to-many relationship with eggs.
     */
    public function eggs()
    {
        return $this->belongsToMany(
            \App\Models\Egg::class,
            'egg_feature_flag',
            'feature_flag_id',
            'egg_id'
        );
    }
}
