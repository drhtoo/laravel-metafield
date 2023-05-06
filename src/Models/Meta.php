<?php

namespace Drhtoo\MetaField\Models;

use Drhtoo\MetaField\Models\Collections\MetaCollection;
use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    protected $attributes = [
        'key'   => '',
        'value' => '',
    ];

    protected $fillable = [
        'key',
        'value'
    ];

    public function newCollection(array $models = []) : MetaCollection
    {
        return new MetaCollection($models);
    }

    public function object()
    {
        return $this->morphTo();
    }
}