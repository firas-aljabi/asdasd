<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class OrderProduct extends Pivot
{
    protected $table = 'orders_products';
    protected $fillable = ['order_id', 'product_id', 'quantity'];
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
   
    public function orders(){
        return $this->belongsTo(Order::class);
    }
}
