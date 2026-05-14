<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // أدمن ثابت من أجل التجربة فقط
        User::factory()->create(
            [
                'first_name'=>'talal100',
                'last_name'=>'ahmad',
                'email'=>'talal100@gmail.com',
                'role'=>'admin',
                'phone'=>'0981722797'
            ]);
            // 10 إخصائيين 
            User::factory()->count(10)->create(['role'=>'specialist']);
            // نضع 30 زبون مثلا
            User::factory()->count(30)->create(['role'=>'client']);
    }
}
