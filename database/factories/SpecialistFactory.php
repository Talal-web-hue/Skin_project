<?php

namespace Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Model>
 */
class SpecialistFactory extends Factory
{
    protected $model = \App\Models\Specialists::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'specialization'=>fake()->randomElement(
                [
                'أخصائي جلدية وتناسلية',
                'أخصائي تجميل وليزر',
                'أخصائي عناية بالبشرة',
                'أخصائي تغذية علاجية',
                'أخصائي علاج طبيعي'
                ]),
                'bio'=>fake()->text(200) ,
                'is_active'=>fake()->boolean(80) , //  اي 80 بالمية نشط و 20 بالمية غير نشط
                'rating'=>fake()->optional(0.8)->randomFloat(2 , 3.50 , 5.00)  // اي 80 بالمية له تقييم و 20 بالمية لا يوجد 
        ];
    }
}
