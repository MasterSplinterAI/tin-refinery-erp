<?php

namespace Database\Factories;

use App\Domain\Inventory\Models\InventoryItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryItemFactory extends Factory
{
    protected $model = InventoryItem::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'type' => $this->faker->randomElement(['cassiterite', 'ingot', 'finished_tin', 'slag']),
            'description' => $this->faker->sentence,
            'quantity' => $this->faker->randomFloat(2, 0, 1000),
            'unit' => $this->faker->randomElement(['kg', 'ton', 'pieces']),
            'sn_content' => $this->faker->randomFloat(2, 0, 100),
            'location' => $this->faker->word,
            'status' => $this->faker->randomElement(['active', 'archived']),
        ];
    }
} 