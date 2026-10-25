<?php

namespace Database\Factories;

use App\Models\Batch;
use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(10, 100);

        return [
            'medicine_id' => Medicine::factory(),
            'batch_number' => 'B-'.fake()->unique()->numerify('#####'),
            'expiry_date' => today()->addYear()->toDateString(),
            'initial_quantity' => $quantity,
            'current_quantity' => $quantity,
        ];
    }

    public function expiresIn(int $days): static
    {
        return $this->state(fn () => ['expiry_date' => today()->addDays($days)->toDateString()]);
    }

    public function quantity(int $quantity): static
    {
        return $this->state(fn () => [
            'initial_quantity' => $quantity,
            'current_quantity' => $quantity,
        ]);
    }
}
