<?php

namespace App\Models;

use App\Http\Resources\OrderDetailsResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use function PHPUnit\Framework\isEmpty;

class Order extends Model
{
    use HasFactory;
    protected $guarded = [];

   
    public function products()
    {
        return $this->belongsToMany(Product::class, 'orders_products')->withPivot('order_id', 'product_id', 'quantity');
    }
    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'orders_ingredients')->withPivot('order_id', 'ingredient_id','quantity');
    }

    public function ordersproducts()
    {
        return $this->belongsToMany(OrderProduct::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    
    
}
