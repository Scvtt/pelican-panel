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

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'string',
    ];

    /**
     * Prevent saving to database
     */
    public function save(array $options = [])
    {
        throw new \Exception('ArrayMod cannot be saved to database');
    }

    /**
     * Prevent deleting from database
     */
    public function delete()
    {
        throw new \Exception('ArrayMod cannot be deleted from database');
    }

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function collection(array $models = [])
    {
        return new Collection($models);
    }

    /**
     * Create a new model instance from an array
     *
     * @param array $attributes
     * @return static
     */
    public static function fromArray(array $attributes)
    {
        $model = new static($attributes);
        $model->exists = true;
        return $model;
    }

    /**
     * Create a collection of models from an array of arrays
     *
     * @param array $items
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function hydrate(array $items)
    {
        $models = [];
        
        foreach ($items as $item) {
            // Make sure required fields always exist
            $item['id'] = $item['id'] ?? uniqid();
            $item['name'] = $item['name'] ?? 'Unknown';
            $item['author'] = $item['author'] ?? 'Unknown';
            $item['version'] = $item['version'] ?? 'Unknown';
            
            $models[] = static::fromArray($item);
        }
        
        return static::collection($models);
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