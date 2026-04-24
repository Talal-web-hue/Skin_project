<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $guarded = [];

 public function user()
 {
    return $this->belongsTo(User::class , 'user_id');
 }


 public function service()
 {
   return $this->belongsTo(Service::class , 'service_id');
 }


 public function specialist()
{
    return $this->belongsTo(Specialists::class , 'specialist_id');
}
    }
