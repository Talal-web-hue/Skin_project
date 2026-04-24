<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = [];
    

    public function product()
    {
        return $this->belongsTo(Product::class , 'product_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class , 'service_id');
    }
}
