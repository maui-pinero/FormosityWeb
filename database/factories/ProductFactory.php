<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $title = fake()->unique()->name();
        $slug = Str::slug($title);

        $subCategories = [5,6];
        $subCatRandKey = array_rand($subCategories);

        return [
            'title' => $title,
            'slug' => $slug,
            'category_id' => 45,
            'sub_category_id' => $subCategories[$subCatRandKey],
            'price' => rand(10,1000),
            'sku' => rand(1000,100000),
            'track_qty' => 'Yes',
            'qty' => 10,
            'is_featured' => 'Yes',
            'status' => 1
        ];
    }
}
