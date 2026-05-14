<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 

class Service extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function appointments()
    {
        return $this->hasMany(Appointment::class , 'service_id');
    }

    public function reviews()
    {
        return $this->morphTo(Review::class , 'reviewable')->where('is_visible' , true);
    }

    
}
