<?php 

namespace Drhtoo\MetaField\Models\Collections;

use Drhtoo\MetaField\Models\Meta;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

class MetaCollection extends Collection
{
    public function find($key, $default = null)
    {
        if ($key instanceof Meta || $key instanceof Arrayable || is_array($key)) {
            return parent::find($key, $default);
        }

        return Arr::first($this->items, function ($meta) use ($key) {
            return $meta->key == $key;
        }, $default);
    }

    public function __get($key)
    {
        if (in_array($key, static::$proxies)) {
            return parent::__get($key);
        }

        if (isset($this->items) && count($this->items)) {
            $meta = $this->first(function ($meta) use ($key) {
                return $meta->key === $key;
            });

            return $meta ? $meta->value : null;
        }

        return null;
    }

    public function __set($key, $value)
    {
        if (in_array($key, static::$proxies)) {
            return parent::__set($key, $value);
        }

        if (isset($this->items) && count($this->items)) {
            $meta = $this->first(function ($meta) use ($key) {
                return $meta->key === $key;
            });
            
            if ($meta) {
                $meta->value = $value;
            } else {
                $meta = new Meta([
                    'key'   => $key,
                    'value' => $value,
                ]);
                $this->push($meta);
            }
        } else {
            $meta = new Meta([
                'key'   => $key,
                'value' => $value,
            ]);

            $this->push($meta);
        }

        return $meta;
    }

    public function __isset($name)
    {
        return !is_null($this->__get($name));
    }
}