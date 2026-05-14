<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       Service::factory()->count(30)->create();

       Service::factory()->create(
        [
            'name' => 'استشارة بشرة أولية',
            'description' => 'جلسة تشخيصية مع أخصائي لتحديد نوع البشرة وخطة العلاج المناسبة',
            'duration' => 30,
            'price' => 0.00, // مجانية لجذب العملاء
            'is_active' => true,            
        ]);
           Service::factory()->create([
            'name' => 'باقة العناية الكاملة',
            'description' => 'باقة شاملة تتضمن تنظيف، تقشير، ترطيب، وعلاج مخصص',
            'duration' => 120,
            'price' => 1200.00,
            'is_active' => true,
        ]);
                $this->command->info(' تم إنشاء ' . Service::count() . ' خدمة بنجاح.');  // هذا الرد يظهر عند تنفيذ اليوزر سيدر في التيرمنال ليخبرك بعدد الخدمات التي تم إنشاؤها.

    }
}
