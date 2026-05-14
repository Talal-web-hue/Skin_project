<?php

namespace Database\Seeders;

use App\Models\Specialists;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecialistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
             // جلب جميع المستخدمين الذين دورهم أخصائي
        $specialistUsers = User::where('role', 'specialist')->get();

        if ($specialistUsers->isEmpty()) {
            $this->command->error('⚠️ لا يوجد مستخدمين بصلاحية specialist. يرجى تشغيل UserSeeder أولاً.');
            return;
        }

          // ربط كل أخصائي بمستخدم فريد (علاقة 1:1)
        foreach ($specialistUsers as $user) {
            Specialists::factory()->create([
                'user_id' => $user->id
            ]);
        }

        $this->command->info("✅ تم إنشاء " . $specialistUsers->count() . " ملف أخصائي بنجاح.");
    
    }
}
