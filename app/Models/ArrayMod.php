<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArrayMod extends Model
{
    protected $table = null; // No table
    public $timestamps = false;
    protected $guarded = [];

    // Prevent saving, deleting, etc.
    public function save(array $options = []) { return false; }
    public function delete() { return false; }
} 