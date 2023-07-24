<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $guarded = [];
    
    public function ratings(){
        return $this->hasMany(Rating::class);
    }
    

    public function orders(){
        return $this->belongsToMany(Order::class,'orders_products')->withPivot('quantity','order_id','product_id');
    }
    public function category(){
    	return $this->belongsTo(Category::class);
    }

    public function branch(){
    	return $this->belongsTo(Branch::class);
    }

    public function ingredients(){

        return $this->belongsToMany(Ingredient::class,'products_ingredients');

    }
    



}
