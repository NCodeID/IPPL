<?php

namespace Database\Factories;

use App\Models\Table;
use Illuminate\Database\Eloquent\Factories\Factory;

class TableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'T-'.$this->faker->unique()->numberBetween(1, 100),
            'status' => Table::STATUS_AVAILABLE,
            'is_active' => true,
        ];
    }
}
