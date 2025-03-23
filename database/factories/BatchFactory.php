<?php

namespace Database\Factories;

use App\Domain\Batch\Models\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatchFactory extends Factory
{
    protected $model = Batch::class;

    public function definition(): array
    {
        return [
            'batchNumber' => $this->faker->unique()->numerify('BATCH-####'),
            'date' => $this->faker->dateTime(),
            'status' => $this->faker->randomElement(['in_progress', 'completed', 'cancelled']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
} 