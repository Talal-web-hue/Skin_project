<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Cache\RetrievesMultipleKeys;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; 
class User extends Authenticatable
{   
    use HasApiTokens, HasFactory, Notifiable;

    // use HasFactory, Notifiable;

    //  protected $primaryKey = 'user_id';  
     protected $guarded = [];

    public function specialist()
    {
        return $this->hasOne(Specialists::class , 'user_id');  
    }


    public function appointments()
    {
        return $this->hasMany(Appointment::class , 'user_id');
    }


    public function orders()
    {
        return $this->hasMany(Order::class , 'user_id');
    }

    
    protected $hidden = [
        'password',
        'remember_token',
    ];

    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
