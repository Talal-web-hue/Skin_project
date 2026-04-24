<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class , 'order_id');
    }

 
    public function product()
    {
        return $this->belongsTo(Order::class , 'product_id');
    }

}
