<?php

namespace Drhtoo\MetaField\Models\Concerns;

use Drhtoo\MetaField\Models\Meta;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Spatie\Translatable\Events\TranslationHasBeenSetEvent;
use Spatie\Translatable\Translatable;
use Spatie\Translatable\HasTranslations;

trait HasMeta
{
    protected $metasForDeletion = [];

    public static function bootHasMeta()
    {
        static::saved(function ($model) {
            foreach ($model->metas as $meta) {
                if (array_key_exists($meta->key, $model->metasForDeletion)) {
                    $meta->delete();
                } else {
                    $model->metas()->save($meta);
                }
            }
        });

        static::deleting(function ($model) {
            foreach ($model->metas as $meta) {
                $meta->delete();
            }
        });
    }

    public function saveMeta($key = null, $value = null)
    {
        if ($key === null) {
            foreach ($this->metas as $meta) {
                $this->metas()->save($meta);
            }

            return true;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->saveOneMeta($k, $v);
            }
            $this->load('metas');

            return true;
        }

        return $this->saveOneMeta($key, $value);
    }

    protected function saveOneMeta($key, $value)
    {
        $meta = $this->metas()->firstOrNew(['key' => $key]);

        $meta->fill(['value' => $value]);

        return $this->metas()->save($meta);
    }

    public function setMeta($key, $value = null)
    {
        if (is_array($key)) {

            return collect($key)->map(function ($value, $key) {
                return $this->setMeta($key, $value);
            });
        }

        return $this->metas->{$key} = $value;
    }

    public function createMeta($key, $value = null)
    {
        if (is_array($key)) {
            return collect($key)->map(function ($value, $key) {
                return $this->createOneMeta($key, $value);
            });
        }

        return $this->createOneMeta($key, $value);
    }

    protected function createOneMeta($key, $value)
    {
        return $this->metas()->create([
            'key' => $key,
            'value' => $value,
        ]);
    }

    public function getAttribute($key)
    {
        if ($this->isMetaAttribute($key)) {
            $value = $this->getMetaAttribute($key);

            if (in_array(HasTranslations::class, class_uses_recursive($this)) && $this->isTranslatableAttribute($key)) {

                $normalizedLocale = $this->normalizeLocale($key, $locale = $this->getLocale(), true);

                $isKeyMissingFromLocale = ($locale !== $normalizedLocale);

                $translation = $value[$normalizedLocale] ?? '';

                $translatableConfig = app(Translatable::class);

                if ($isKeyMissingFromLocale && $translatableConfig->missingKeyCallback) {
                    try {
                        $callbackReturnValue = (app(Translatable::class)->missingKeyCallback)($this, $key, $locale, $translation, $normalizedLocale);
                        if (is_string($callbackReturnValue)) {
                            $translation = $callbackReturnValue;
                        }
                    } catch (Exception) {
                        //prevent the fallback to crash
                    }
                }

                if ($this->hasGetMutator($key)) {
                    return $this->mutateAttribute($key, $translation);
                }

                return $translation;
            }

            return $value;
        }

        return parent::getAttribute($key);
    }

    public function getMetaAttribute($key)
    {
        $metaFields = $this->getMetaFields();

        $value = method_exists($this, $getter = 'get' . Str::studly($key) . 'Attribute')
            ? $this->{$getter}()
            : $this->metas->{$key} ?? $metaFields[$key];

        if ($value && $this->hasCast($key)) {
            $value = $this->castAttribute($key, $value);
        }

        return $value;
    }

    public function setAttribute($key, $value)
    {
        if ($this->isMetaAttribute($key)) {
            if (in_array(HasTranslations::class, class_uses_recursive($this)) && $this->isTranslatableAttribute($key)) {
                if (is_array($value)) {
                    foreach ($value as $locale => $translation) {
                        $this->setMetaTranslation($key, $locale, $translation);
                    }
                    return $this;
                } else {
                    return $this->setMetaTranslation($key, $this->getLocale(), $value);
                }
            }

            return $this->setMetaAttribute($key, $value);
        }

        if (in_array(HasTranslations::class, class_uses_recursive($this)) && $this->isTranslatableAttribute($key)) {
            if (is_array($value)) {
                return $this->setTranslations($key, $value);
            } else {
                return $this->setTranslation($key, $this->getLocale(), $value);
            }
        }

        return parent::setAttribute($key, $value);
    }

    public function setMetaAttribute($key, $value)
    {
        $metaFields = $this->getMetaFields();

        if ($value === $metaFields[$key]) {
            $this->metasForDeletion[$key] = $value;
        } elseif (isset($this->metasForDeletion[$key]) && $value !== $this->metasForDeletion[$key]) {
            unset($this->metasForDeletion[$key]);
        }

        if (method_exists($this, $setter = 'set' . Str::studly($key) . 'Attribute')) {
            return $this->{$setter}($value);
        }

        if (!is_null($value) && $this->isJsonCastable($key)) {
            $value = $this->castAttributeAsJson($key, $value);
        }

        return $this->metas->{$key} = $value;
    }

    public function mutateAttribute($key, $value)
    {
        if ($this->hasGetMutator($key)) {
            return parent::mutateAttribute($key, $value);
        } elseif ($this->isMetaAttribute($key)) {
            return $this->getMetaAttribute($key);
        }

        return $this->getAttribute($key);
    }

    public function setMetaTranslation(string $key, string $locale, $value): self
    {
        $this->guardAgainstNonTranslatableAttribute($key);

        $translations = $this->getMetaAttribute($key);

        $oldValue = $translations[$locale] ?? '';

        if ($this->hasSetMutator($key)) {
            $method = 'set' . Str::studly($key) . 'Attribute';

            $this->{$method}($value, $locale);

            $value = $this->metas->{$key};
        }

        $translations[$locale] = $value;

        $this->metas->{$key} = $this->asJson($translations);

        event(new TranslationHasBeenSetEvent($this, $key, $locale, $oldValue, $value));

        return $this;
    }

    public function isMetaAttribute($key)
    {
        return array_key_exists($key, $this->getMetaFields());
    }

    public function getMetaFields()
    {
        return property_exists($this, 'metaFields') ? $this->metaFields : [];
    }

    public function metas(): MorphMany
    {
        return $this->morphMany(Meta::class, 'object', 'object_type', 'object_id');
    }

    public function scopeHasMeta(Builder $query, $meta, $value = null, string $operator = '=')
    {
        if (!is_array($meta)) {
            $meta = [$meta => $value];
        }

        foreach ($meta as $key => $value) {
            $query->whereHas('metas', function (Builder $query) use ($key, $value, $operator) {
                if (!is_string($key)) {
                    return $query->where('key', $operator, $value);
                }

                $query->where('key', $operator, $key);

                return is_null($value) ? $query :
                    $query->where('value', $operator, $value);
            });
        }

        return $query;
    }

    public function scopeHasMetaLike(Builder $query, $meta, $value = null)
    {
        return $this->scopeHasMeta($query, $meta, $value, 'like');
    }

    public function scopeDoesntHaveMeta(Builder $query, $meta)
    {
        return $query->whereDoesntHave('metas', function (Builder $query) use ($meta) {
            return $query->where('key', $meta);
        });
    }
}
