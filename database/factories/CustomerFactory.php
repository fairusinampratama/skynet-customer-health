<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Customer;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'ip_address' => $this->faker->ipv4,
            'status' => 'up',
            'is_isolated' => false,
            'area_id' => 1, // Overridden by seeder
        ];
    }
}
