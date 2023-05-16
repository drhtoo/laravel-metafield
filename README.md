# Laravel Metafield Package
The Laravel Metafield package allows you to add custom metafields to your Laravel Eloquent models. This is useful when you need to store additional data about a model beyond its basic attributes.

## Installation
To install the package, you can simply run the following Composer command:

```composer require drhtoo/laravel-metafield```

After you've installed the package, you'll need to publish the package's migrations to your Laravel application. You can do this by running the following command:

```php artisan vendor:publish --tag=laravel-metafield-migrations```

Once the migrations have been published, you can run them by using the following command:

```php artisan migrate```

## Usage
To start using the Laravel Metafield package, you'll need to add the HasMeta trait to any Eloquent models that you want to have metafields. Here's an example:

```
use Drhtoo\MetaField\Models\Concerns\HasMeta;

class Product extends Model
{
    use HasMeta;
}
```

Now, you can use related meta fields by using relationship **metas**

```
$product = Product::find(1);

$product->metas->price = 100;

echo $product->metas->price; // 100
```

### MetaFields Property
To use meta fields as a model attribute, you have to add ***$metaFields*** property to your model. It is a protected property of array type which is the default values keyed by the field/key.

```
use Drhtoo\MetaField\Models\Concerns\HasMeta;

class Product extends Model
{
    use HasMeta;

    protected $metaFields = [
        'price' => null,
        'is_sale' => false,
        'sale_price' => null,
        'color' => 'white',
    ];
}

$product = Product::find(1);

$product->price = 100;

echo $product->price; // 100
```

### Attribute Casting 
You can also cast the metafield as in attributes of Eloquent/Models.

```
use Drhtoo\MetaField\Models\Concerns\HasMeta;

class Product extends Model
{
    use HasMeta;

    protected $metaFields = [
        'price' => null,
        'is_sale' => false,
        'sale_price' => null,
        'color' => null,
        'sale_start' => null,
        'sale_end' => null,
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'color' => 'array',
        'sale_start' => 'datetime',
        'sale_end' => 'datetime',
    ];
}

$product = Product::find(1);

$product->price = 100;

echo $product->price; // 100
```

### Working with Livewire Component
This package is well compatible with Livewire component and directly bind to wire:model attribute.

```
<input type="number" wire:model="product.price" />
@error('product.price')
<span class="error">{{ $message }}</span>
@enderror 
```

### Working with Spatie's Laravel-translatable Package
Laravel Meta Field works well with spatie/laravel-translatable package and you just simply add array item of meta field to ***$translatable*** property of your model and use ***setAttribute*** method of ***HasMeta*** trait insteadof ***Translatable***.

```
use Drhtoo\MetaField\Models\Concerns\HasMeta;

class Product extends Model
{
    use HasMeta {
        HasMeta::setAttribute insteadof HasTranslations;
    }

    protected $attributes = [
        'title' => null,
        'description' => null,
    ];

    protected $metaFields = [
        'price' => null,
        'is_sale' => false,
        'sale_price' => null,
        'sale_description' => null,
    ];

    public $translatable = [
        'title',
        'description',
        'sale_description'
    ];
}


$product = Product::find(1);

$product->sale_description = 'This is sale product'; // set value to current locale

$product->setTranslation('sale_description', 'es', 'Este es un producto de venta'); 
```

That's all. Have fun.