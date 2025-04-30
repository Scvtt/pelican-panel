<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModManager extends Model
{
    public function egg()
    {
        return $this->belongsTo(\App\Models\Egg::class);
    }
}
