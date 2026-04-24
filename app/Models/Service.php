<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $guarded = [];

    public function appointments()
    {
        return $this->hasMany(Appointment::class , 'service_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class , 'service_id');
    }

    
}
