<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ArrayMod extends Model
{
    protected $table = null; // No table
    public $timestamps = false;
    protected $guarded = [];

    // Prevent saving, deleting, etc.
    public function save(array $options = []) { return false; }
    public function delete() { return false; }
    
    /**
     * Helper method for setCollection
     */
    public static function collection(Collection $collection)
    {
        return new Collection($collection->all());
    }
    
    /**
     * Allow setting a collection on the builder
     */
    public function newEloquentBuilder($query)
    {
        $builder = new class($query) extends Builder {
            protected $customCollection;
            
            public function setCollection(Collection $collection)
            {
                $this->customCollection = $collection;
                return $this;
            }
            
            public function get($columns = ['*'])
            {
                if ($this->customCollection) {
                    return $this->customCollection;
                }
                
                return parent::get($columns);
            }
        };
        
        return $builder;
    }
} 