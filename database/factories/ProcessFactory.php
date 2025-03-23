<?php

namespace Database\Factories;

use App\Domain\Process\Models\Process;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProcessFactory extends Factory
{
    protected $model = Process::class;

    public function definition(): array
    {
        return [
            'batchId' => \App\Domain\Batch\Models\Batch::factory(),
            'processNumber' => $this->faker->numberBetween(1, 10),
            'processingType' => 'kaldo_furnace',
            'inputTinKilos' => $this->faker->randomFloat(2, 100, 1000),
            'inputTinSnContent' => $this->faker->randomFloat(2, 20, 80),
            'outputTinKilos' => $this->faker->randomFloat(2, 90, 950),
            'outputTinSnContent' => $this->faker->randomFloat(2, 30, 90),
            'inputSlagKilos' => $this->faker->randomFloat(2, 10, 100),
            'inputSlagSnContent' => $this->faker->randomFloat(2, 5, 15),
            'outputSlagKilos' => $this->faker->randomFloat(2, 5, 90),
            'outputSlagSnContent' => $this->faker->randomFloat(2, 1, 10),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
} 