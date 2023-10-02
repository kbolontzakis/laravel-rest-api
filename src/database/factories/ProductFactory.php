<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(str_replace('.', '', $this->faker->unique()->text(10)));

        return [
            'name' => $name,
            'code' => Str::slug($name),
            'category_id' => rand(1, 3),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'release_date' => $this->faker->date(),
        ];
    }
}
