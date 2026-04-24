<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialists extends Model
{
        protected $guarded = [];
   
   public function user()
   {
        return $this->belongsTo(User::class , 'user_id');
   }

   public function appointments()
   {
     return $this->hasMany(Appointment::class , 'specialist_id');
   }
}
