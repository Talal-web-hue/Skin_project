<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];


    //  اي المنتج له تقييم واحد او عدة تقييمات
    public function reviews()
    {
        return $this->morphTo(Review::class, 'reviewable')->where('is_visible' , true);
    }


    public function items()
    {
        return $this->hasMany(OrderItem::class, 'product_id'); 
    }
}
