<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'=>fake()->randomElement(
                [
               'جلسة تنظيف بشرة عميق',
                'تقشير كيميائي للبشرة',
                'جلسة هيدرا فيشال',
                'علاج الليزر للتصبغات',
                'جلسة شد الوجه بالترددات',
                'علاج حب الشباب المتقدم',
                'جلسة تفتيح البشرة بالفيتامينات',
                'علاج التجاعيد بالبوتوكس',
                'جلسة تقشير الميكرو ديرما',
                'علاج الهالات السوداء حول العين'
                ]),
            'description'=>fake()->text(200) ,
            'price'=>fake()->randomFloat(2, 150, 800) ,
            'duration'=>fake()->randomElement([30, 45, 60, 90, 120]) , // مدة الجلسة بين 30 دقيقة و 120 دقيقة
            'is_active'=>fake()->boolean(90)  // اي 90 بالمية متاحة و 10 بالمية غير متاحة
        ];
    }
}
