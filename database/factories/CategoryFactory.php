<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word(), // Tên danh mục ngẫu nhiên (1 từ)
            'user_id' => function () {
                return \App\Models\User::factory()->create()->id; // Tạo user mới
            },
        ];
    }
}
