<?php

namespace Database\Factories;

use Core\Database\Internal\Factory;
use App\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'full_name' => fake()->name(),
            'email' => fake()->email(),
            'password' => fake()->password(),
            'remember_token' => fake()->uuid(),
        ];
    }
}