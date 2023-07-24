<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductIngredient extends Pivot
{
    protected $table = 'products_ingredients';
    protected $fillable=[
        'product_id',
        'ingredient_id',
        ];

    public function ingredients()
    {
      return $this->belongsToMany(Ingredient::class);
    }
    
    public function products()
    {
      return $this->belongsToMany(Product::class);
    }
}
