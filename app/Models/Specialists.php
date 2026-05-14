<?php

namespace App\Models;
use Database\Factories\SpecialistFactory; 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory; 
class Specialists extends Model
{
     use HasFactory;
        protected $guarded = [];
   

            //  هذا السطر يخبر لارافيل باسم الفاكتوري الصحيح
    protected static function newFactory() {
        return SpecialistFactory::new();
    }


   public function user()
   {
        return $this->belongsTo(User::class , 'user_id');
   }

   public function appointments()
   {
     return $this->hasMany(Appointment::class , 'specialist_id');
   }
}
