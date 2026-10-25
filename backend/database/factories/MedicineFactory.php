<?php

namespace Database\Factories;

use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicine>
 */
class MedicineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'MED-'.fake()->unique()->numerify('####'),
            'name' => fake()->words(2, true),
            'unit' => 'tablet',
            'min_stock' => 0,
        ];
    }
}
