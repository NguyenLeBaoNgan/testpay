<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\Category;
use App\Models\CategoryProduct;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(), // Đoạn mô tả ngẫu nhiên
            'price' => $this->faker->randomFloat(2, 10000, 1000000), // Giá từ 10k đến 1M VND
            'quantity' => $this->faker->numberBetween(1, 100), // Số lượng từ 1 đến 100
            'user_id' => function () {
                return \App\Models\User::factory()->create()->id;
            },
            'image' => $this->faker->imageUrl(640, 480, 'products', true), // URL ảnh giả
        ];
    }

    // Tùy chỉnh để gán danh mục sau khi tạo sản phẩm
    public function configure()
    {
        // dd(class_exists('App\Models\CategoryProduct'));

        return $this->afterCreating(function (Product $product) {
            $categories = Category::factory()->count($this->faker->numberBetween(1, 3))->create();

            $categoryProductData = $categories->map(function ($category) use ($product) {
                return [
                    'id' => (string) \Illuminate\Support\Str::ulid(), // Tạo ULID thủ công
                    'category_id' => $category->id,
                    'product_id' => $product->id,
                ];
            })->toArray();

            CategoryProduct::insert($categoryProductData);
        });
    }
}
